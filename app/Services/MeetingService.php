<?php

namespace App\Services;

use App\Exceptions\MeetingException;
use App\Mail\MeetingNotification;
use App\Models\Meeting;
use App\Models\User;
use App\Models\ZoomAccount;
use App\Services\Zoom\ZoomClient;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Everything the Supabase Edge Functions used to do:
 * schedule-meeting, cancel-meeting, get-start-url, get-recording and add-zoom-account.
 */
class MeetingService
{
    public function __construct(private ZoomClient $zoom)
    {
    }

    /* =====================================================================
     |  Schedule
     | ==================================================================== */

    /**
     * Finds a free Zoom account for the requested slot and creates the meeting.
     *
     * @param  array{title:string, start_time:mixed, duration_min:int|string, agenda?:?string, department?:?string, participants_est?:int|string|null}  $input
     * @return array{status:'scheduled', meeting:Meeting}
     *       | array{status:'unavailable', message:string, suggestions:array<int,array{start:Carbon,end:Carbon}>}
     *       | array{status:'error', error:string}
     */
    public function schedule(User $user, array $input): array
    {
        $min = (int) config('portal.min_duration');
        $max = (int) config('portal.max_duration');
        $buffer = (int) config('portal.buffer_minutes');

        // ---- validate ----
        try {
            $v = Validator::make($input, [
                'title' => ['required', 'string', 'min:3', 'max:120'],
                'start_time' => ['required', 'date'],
                'duration_min' => ['required', 'integer', "min:$min", "max:$max"],
                'agenda' => ['nullable', 'string', 'max:2000'],
                'department' => ['nullable', 'string', 'max:120'],
                'participants_est' => ['nullable', 'integer', 'min:1', 'max:1000'],
            ], [
                'title.required' => 'Please enter a meeting title.',
                'title.min' => 'Please enter a meeting title.',
                'duration_min.min' => "Duration must be $min-$max minutes.",
                'duration_min.max' => "Duration must be $min-$max minutes.",
            ])->validate();
        } catch (ValidationException $e) {
            return ['status' => 'error', 'error' => $e->validator->errors()->first()];
        }

        $start = Carbon::parse($v['start_time'])->startOfMinute();
        if ($start->isPast()) {
            return ['status' => 'error', 'error' => 'Start time is in the past.'];
        }

        $duration = (int) $v['duration_min'];
        $end = $start->copy()->addMinutes($duration);
        $blockEnd = $end->copy()->addMinutes($buffer);

        $fields = [
            'user_id' => $user->id,
            'title' => trim($v['title']),
            'agenda' => filled($v['agenda'] ?? null) ? trim($v['agenda']) : null,
            'department' => filled($v['department'] ?? null) ? trim($v['department']) : null,
            'participants_est' => filled($v['participants_est'] ?? null) ? (int) $v['participants_est'] : null,
            'start_time' => $start,
            'end_time' => $end,
            'block_start' => $start,
            'block_end' => $blockEnd,
        ];

        // ---- active accounts, lowest priority number first ----
        $accounts = ZoomAccount::where('is_active', true)->orderBy('priority')->orderBy('created_at')->get();
        if ($accounts->isEmpty()) {
            return ['status' => 'error', 'error' => 'No Zoom accounts are configured yet. Please contact the admin.'];
        }

        // ---- everything already booked around the requested time (wide enough for the suggestions below) ----
        $busy = Meeting::scheduled()
            ->where('block_start', '<', $start->copy()->addHours(9)->addMinutes($duration + $buffer))
            ->where('block_end', '>', $start->copy()->subHours(3))
            ->get(['zoom_account_id', 'block_start', 'block_end']);

        $freeAccounts = $accounts->filter(fn (ZoomAccount $a) => $this->isFree($busy, $a->id, $start, $end));

        // ---- try each free account: reserve the slot first, then call Zoom ----
        $zoomError = '';
        foreach ($freeAccounts as $acc) {
            try {
                $row = $this->reserve($acc, $fields);
            } catch (LockTimeoutException) {
                return ['status' => 'error', 'error' => 'The portal is busy right now. Please try again in a moment.'];
            }

            if (! $row) {
                continue; // someone else took this account a moment ago
            }

            try {
                $z = $this->zoom->createMeeting($acc->host_email, $row->title, $row->agenda, $start, $duration);
                $row->update([
                    'zoom_meeting_id' => (string) $z['id'],
                    'join_url' => $z['join_url'],
                    'passcode' => $z['password'] ?? null,
                ]);
            } catch (Throwable $e) {
                // Zoom problem on this account: release the slot and try the next one
                $zoomError = $e->getMessage();
                report($e);
                $row->delete();

                continue;
            }

            $this->mail($user->email, $row, 'confirm');

            return ['status' => 'scheduled', 'meeting' => $row->fresh()];
        }

        // accounts were free but Zoom refused every one: this is not "busy", so say what happened
        if ($freeAccounts->isNotEmpty() && $zoomError !== '') {
            return ['status' => 'error', 'error' => "Zoom could not create the meeting: {$zoomError}"];
        }

        // ---- nothing free: record the failed request and suggest other slots ----
        Meeting::create($fields + ['status' => Meeting::FAILED]);

        return [
            'status' => 'unavailable',
            'message' => 'All Zoom accounts are busy at that time. Please choose another slot.',
            'suggestions' => $this->suggest($accounts, $busy, $start, $duration, $buffer),
        ];
    }

    /**
     * Re-checks the account under a lock and inserts the reservation.
     * SQLite has no "exclusion constraint", so this lock is what stops two people booking the same slot.
     */
    private function reserve(ZoomAccount $acc, array $fields): ?Meeting
    {
        return Cache::lock('meetings:reserve', 15)->block(10, function () use ($acc, $fields) {
            $clash = Meeting::scheduled()
                ->where('zoom_account_id', $acc->id)
                ->where('block_start', '<', $fields['block_end'])
                ->where('block_end', '>', $fields['block_start'])
                ->exists();

            if ($clash) {
                return null;
            }

            return Meeting::create($fields + ['status' => Meeting::SCHEDULED, 'zoom_account_id' => $acc->id]);
        });
    }

    /** Is this account free for [start, end] plus the buffer? */
    private function isFree(Collection $busy, int $accountId, Carbon $start, Carbon $end): bool
    {
        $blockEnd = $end->copy()->addMinutes((int) config('portal.buffer_minutes'));

        return ! $busy->contains(fn ($m) => (int) $m->zoom_account_id === $accountId
            && $m->block_start->lt($blockEnd)
            && $m->block_end->gt($start));
    }

    /**
     * Up to 5 free start times near the requested one (within working hours), earliest first.
     *
     * @return array<int,array{start:Carbon,end:Carbon}>
     */
    private function suggest(Collection $accounts, Collection $busy, Carbon $start, int $duration, int $buffer): array
    {
        $from = (int) config('portal.day_start_hour');
        $to = (int) config('portal.day_end_hour');

        $candidates = collect(range(-120, 480, 30))
            ->reject(fn ($off) => $off === 0)
            ->map(fn ($off) => $start->copy()->addMinutes($off))
            ->reject(fn (Carbon $c) => $c->isPast() || $c->hour < $from || $c->hour >= $to)
            ->sortBy(fn (Carbon $c) => abs($c->diffInMinutes($start, false)))
            ->values();

        $out = [];
        foreach ($candidates as $c) {
            $ce = $c->copy()->addMinutes($duration);
            if ($accounts->contains(fn ($a) => $this->isFree($busy, $a->id, $c, $ce))) {
                $out[] = ['start' => $c, 'end' => $ce];
            }
            if (count($out) === 5) {
                break;
            }
        }

        usort($out, fn ($a, $b) => $a['start']->getTimestamp() <=> $b['start']->getTimestamp());

        return $out;
    }

    /**
     * For every start time offered on a day: is at least one Zoom host free for a meeting of
     * $duration minutes (plus the buffer)? Used to mark taken times in the booking form.
     *
     * @return array<string,bool> ['09:00' => true, '09:15' => false, ...]
     */
    public function slotAvailability(Carbon $day, int $duration, ?int $ignoreMeetingId = null): array
    {
        $accounts = ZoomAccount::where('is_active', true)->get(['id']);

        $open = $day->copy()->startOfDay()->setTime((int) config('portal.day_start_hour'), 0);
        $close = $day->copy()->startOfDay()->setTime((int) config('portal.day_end_hour'), 0);
        $buffer = (int) config('portal.buffer_minutes');

        $busy = Meeting::scheduled()
            ->when($ignoreMeetingId, fn ($q) => $q->whereKeyNot($ignoreMeetingId))
            ->where('block_start', '<', $close->copy()->addMinutes($duration + $buffer))
            ->where('block_end', '>', $open)
            ->get(['zoom_account_id', 'block_start', 'block_end']);

        $out = [];
        for ($t = $open->copy(); $t < $close; $t->addMinutes(15)) {
            $end = $t->copy()->addMinutes($duration);
            $out[$t->format('H:i')] = $accounts->contains(fn ($a) => $this->isFree($busy, $a->id, $t, $end));
        }

        return $out;
    }

    /* =====================================================================
     |  Reschedule
     | ==================================================================== */

    /**
     * Moves an upcoming meeting to a new time. If the same Zoom host is free the join link stays
     * the same; otherwise another free host is used and the link changes (the owner is emailed).
     *
     * @return array{status:'rescheduled', meeting:Meeting, link_changed:bool}
     *       | array{status:'unavailable', message:string, suggestions:array<int,array{start:Carbon,end:Carbon}>}
     *       | array{status:'error', error:string}
     */
    public function reschedule(Meeting $meeting, User $actor, string $startTime, int $durationMin): array
    {
        $this->authorize($meeting, $actor);

        if ($meeting->status !== Meeting::SCHEDULED || ! $meeting->zoom_meeting_id) {
            return ['status' => 'error', 'error' => 'Meeting not found.'];
        }
        if ($meeting->start_time->isPast()) {
            return ['status' => 'error', 'error' => 'This meeting has already started, so it can no longer be moved.'];
        }

        $min = (int) config('portal.min_duration');
        $max = (int) config('portal.max_duration');
        $buffer = (int) config('portal.buffer_minutes');

        if ($durationMin < $min || $durationMin > $max) {
            return ['status' => 'error', 'error' => "Duration must be $min-$max minutes."];
        }

        try {
            $start = Carbon::parse($startTime)->startOfMinute();
        } catch (Throwable) {
            return ['status' => 'error', 'error' => 'Please pick a valid date and time.'];
        }
        if ($start->isPast()) {
            return ['status' => 'error', 'error' => 'Start time is in the past.'];
        }

        $end = $start->copy()->addMinutes($durationMin);
        $blockEnd = $end->copy()->addMinutes($buffer);

        $accounts = ZoomAccount::where('is_active', true)->orderBy('priority')->orderBy('created_at')->get();
        if ($accounts->isEmpty()) {
            return ['status' => 'error', 'error' => 'No Zoom accounts are configured yet. Please contact the admin.'];
        }

        // what to put back if Zoom refuses
        $original = [
            'start_time' => $meeting->getRawOriginal('start_time'),
            'end_time' => $meeting->getRawOriginal('end_time'),
            'block_start' => $meeting->getRawOriginal('block_start'),
            'block_end' => $meeting->getRawOriginal('block_end'),
            'zoom_account_id' => $meeting->zoom_account_id,
        ];
        $oldAccountId = (int) $meeting->zoom_account_id;

        // ---- reserve the new slot under the same lock bookings use ----
        try {
            $picked = Cache::lock('meetings:reserve', 15)->block(10, function () use ($meeting, $accounts, $start, $end, $blockEnd, $oldAccountId) {
                $busy = Meeting::scheduled()
                    ->whereKeyNot($meeting->id)
                    ->where('block_start', '<', $blockEnd)
                    ->where('block_end', '>', $start)
                    ->get(['zoom_account_id', 'block_start', 'block_end']);

                $free = $accounts->filter(fn (ZoomAccount $a) => $this->isFree($busy, $a->id, $start, $end));
                if ($free->isEmpty()) {
                    return null;
                }

                // keep the same host (and join link) when possible
                $acc = $free->firstWhere('id', $oldAccountId) ?? $free->first();

                $meeting->update([
                    'start_time' => $start,
                    'end_time' => $end,
                    'block_start' => $start,
                    'block_end' => $blockEnd,
                    'zoom_account_id' => $acc->id,
                ]);

                return $acc;
            });
        } catch (LockTimeoutException) {
            return ['status' => 'error', 'error' => 'The portal is busy right now. Please try again in a moment.'];
        }

        if (! $picked) {
            $busy = Meeting::scheduled()
                ->whereKeyNot($meeting->id)
                ->where('block_start', '<', $start->copy()->addHours(9)->addMinutes($durationMin + $buffer))
                ->where('block_end', '>', $start->copy()->subHours(3))
                ->get(['zoom_account_id', 'block_start', 'block_end']);

            return [
                'status' => 'unavailable',
                'message' => 'All Zoom accounts are busy at that time. Please choose another slot.',
                'suggestions' => $this->suggest($accounts, $busy, $start, $durationMin, $buffer),
            ];
        }

        // ---- tell Zoom ----
        $linkChanged = false;
        try {
            if ((int) $picked->id === $oldAccountId) {
                $this->zoom->updateMeeting($meeting->zoom_meeting_id, $start, $durationMin);
            } else {
                $z = $this->zoom->createMeeting($picked->host_email, $meeting->title, $meeting->agenda, $start, $durationMin);
                $oldZoomId = $meeting->zoom_meeting_id;

                $meeting->update([
                    'zoom_meeting_id' => (string) $z['id'],
                    'join_url' => $z['join_url'],
                    'passcode' => $z['password'] ?? null,
                ]);
                $linkChanged = true;

                try {
                    $this->zoom->deleteMeeting($oldZoomId);
                } catch (Throwable $e) {
                    report($e); // the old meeting is just left over; the new one is what counts
                }
            }
        } catch (Throwable $e) {
            report($e);
            $meeting->update($original); // put the old time back

            return ['status' => 'error', 'error' => 'Zoom could not move the meeting: '.$e->getMessage()];
        }

        $meeting->update(['reminder_sent' => false]); // remind again for the new time
        $meeting = $meeting->fresh();

        $this->mail($meeting->user?->email, $meeting, 'rescheduled');

        return ['status' => 'rescheduled', 'meeting' => $meeting, 'link_changed' => $linkChanged];
    }

    /* =====================================================================
     |  Cancel / start / recording
     | ==================================================================== */

    public function cancel(Meeting $meeting, User $actor): void
    {
        $this->authorize($meeting, $actor);

        if (! $meeting->isLive()) {
            throw new MeetingException($meeting->status === Meeting::SCHEDULED
                ? 'This meeting has already ended.'
                : 'Meeting not found.');
        }

        // best effort: a Zoom failure must not block freeing the slot
        if ($meeting->zoom_meeting_id) {
            try {
                $this->zoom->deleteMeeting($meeting->zoom_meeting_id);
            } catch (Throwable $e) {
                report($e);
            }
        }

        $meeting->update(['status' => Meeting::CANCELLED]);
    }

    /** Zoom's start_url expires after a few hours, so a fresh one is fetched on every click. */
    public function startUrl(Meeting $meeting, User $actor): string
    {
        $this->authorize($meeting, $actor);

        if ($meeting->status !== Meeting::SCHEDULED || ! $meeting->zoom_meeting_id) {
            throw new MeetingException('Meeting not found.');
        }

        return $this->zoom->getMeeting($meeting->zoom_meeting_id)['start_url'];
    }

    public const RECORDING_LABELS = [
        'shared_screen_with_speaker_view' => 'Video – shared screen with speaker',
        'shared_screen_with_gallery_view' => 'Video – shared screen with gallery',
        'shared_screen' => 'Video – shared screen',
        'active_speaker' => 'Video – active speaker',
        'gallery_view' => 'Video – gallery view',
        'audio_only' => 'Audio only',
        'audio_transcript' => 'Transcript',
        'chat_file' => 'Chat log',
        'closed_caption' => 'Captions',
    ];

    /**
     * Download links for the cloud recording of a FINISHED meeting.
     *
     * @return array{files: array<int,array{label:string,type:string,size:int,url:string}>, message: ?string}
     */
    public function recordings(Meeting $meeting, User $actor): array
    {
        $this->authorize($meeting, $actor);

        if ($meeting->status !== Meeting::SCHEDULED || ! $meeting->zoom_meeting_id) {
            throw new MeetingException('Meeting not found.');
        }
        if ($meeting->end_time->isFuture()) {
            throw new MeetingException('The recording is available after the meeting has finished.');
        }

        if ($meeting->recordings_status === Meeting::REC_PURGED) {
            $months = (int) config('portal.recording_retention_months');

            return [
                'files' => [],
                'message' => 'This recording was removed after the '.$months.'-month retention period.',
            ];
        }

        // After the nightly job the files live in Google Drive (and are gone from Zoom).
        $archived = $meeting->recordings()->orderBy('id')->get();
        if ($archived->isNotEmpty() && $meeting->recordings_synced_at) {
            return $this->archivedFiles($archived);
        }

        $rec = $this->zoom->recordings($meeting->zoom_meeting_id);

        // skip timeline / index files
        $ready = collect($rec['recording_files'] ?? [])
            ->filter(fn ($f) => ($f['status'] ?? '') === 'completed' && in_array($f['file_type'] ?? '', ['MP4', 'M4A', 'TRANSCRIPT', 'CHAT', 'CC'], true))
            ->values();

        if (! $rec || $ready->isEmpty()) {
            if ($archived->isNotEmpty()) {
                return $this->archivedFiles($archived);
            }

            return [
                'files' => [],
                'message' => 'No recording is available. Zoom can take a while to process it after the meeting ends. '
                    .'If it still doesn\'t appear, the host may not have recorded to the cloud.',
            ];
        }

        $tok = $rec['download_access_token'] ?? '';

        return [
            'message' => null,
            'files' => $ready->map(fn ($f) => [
                'label' => self::RECORDING_LABELS[$f['recording_type'] ?? ''] ?? ($f['recording_type'] ?? $f['file_type']),
                'type' => $f['file_type'],
                'size' => (int) ($f['file_size'] ?? 0),
                'url' => $tok
                    ? $f['download_url'].(str_contains($f['download_url'], '?') ? '&' : '?').'access_token='.$tok
                    : $f['download_url'],
            ])->all(),
        ];
    }

    /**
     * Recordings already copied to Google Drive; downloads go through the portal.
     *
     * @param  \Illuminate\Support\Collection<int,\App\Models\MeetingRecording>  $rows
     */
    private function archivedFiles(Collection $rows): array
    {
        return [
            'message' => null,
            'archived' => true,
            'files' => $rows->map(fn ($r) => [
                'label' => self::RECORDING_LABELS[$r->recording_type ?? ''] ?? ($r->recording_type ?: $r->file_type),
                'type' => $r->file_type,
                'size' => (int) $r->file_size,
                'url' => route('recordings.download', $r),
            ])->all(),
        ];
    }

    /* =====================================================================
     |  Admin: add a Zoom host
     | ==================================================================== */

    public function addAccount(User $actor, string $label, string $hostEmail, int $priority = 100): ZoomAccount
    {
        if (! $actor->can('admin:zoom-accounts:create')) {
            throw new MeetingException('Admins only.');
        }

        $label = trim($label);
        $hostEmail = strtolower(trim($hostEmail));
        if ($label === '' || $hostEmail === '') {
            throw new MeetingException('Label and host email are required.');
        }
        if (ZoomAccount::where('host_email', $hostEmail)->exists()) {
            throw new MeetingException('This host email is already added.');
        }

        // confirm the credentials work and the host exists as a user on the Zoom account
        $this->zoom->getUser($hostEmail);

        return ZoomAccount::create(['label' => $label, 'host_email' => $hostEmail, 'priority' => $priority]);
    }

    /* =====================================================================
     |  helpers
     | ==================================================================== */

    private function authorize(Meeting $meeting, User $actor): void
    {
        if (! $meeting->isManageableBy($actor)) {
            throw new MeetingException('Not allowed.');
        }
    }

    /** Email is best effort: a mail problem must never undo a meeting that exists in Zoom. */
    public function mail(?string $to, Meeting $meeting, string $kind): void
    {
        if (blank($to)) {
            return;
        }

        try {
            Mail::to($to)->send(new MeetingNotification($meeting, $kind));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
