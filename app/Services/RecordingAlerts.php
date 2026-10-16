<?php

namespace App\Services;

use App\Mail\RecordingAlert;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Emails admins when the recording job fails or a recording is stuck. */
class RecordingAlerts
{
    /**
     * Meetings whose recording should be in Drive by now but is not:
     * failed / still processing / queued, or never checked at all (the nightly job may not be running).
     *
     * @return Collection<int,Meeting>
     */
    public function stale(): Collection
    {
        $after = (int) config('portal.recording_alert_after_days');
        $lookback = (int) config('portal.recording_lookback_days');

        return Meeting::scheduled()
            ->whereNotNull('zoom_meeting_id')
            ->whereNull('recordings_synced_at')
            ->where('end_time', '<=', now()->subDays($after))
            ->where('end_time', '>=', now()->subDays(30))
            ->where(function ($q) use ($lookback) {
                $q->whereIn('recordings_status', [Meeting::REC_FAILED, Meeting::REC_PROCESSING, Meeting::REC_QUEUED])
                    // never checked: only meaningful inside the window the nightly job looks at
                    ->orWhere(fn ($q) => $q->whereNull('recordings_status')->where('end_time', '>=', now()->subDays($lookback)));
            })
            ->orderBy('end_time')
            ->get();
    }

    /**
     * @param  array<int,array{meeting:Meeting,error:string}>  $failures  failed during this run
     * @param  string|null  $general  a problem that is not tied to one meeting (e.g. sign-in failed)
     */
    public function send(array $failures, Collection $stale, ?string $general = null): void
    {
        if ($failures === [] && $stale->isEmpty() && ! $general) {
            return;
        }

        $to = $this->recipients();
        if ($to === []) {
            return;
        }

        try {
            Mail::to($to)->send(new RecordingAlert($failures, $stale, $general));
        } catch (Throwable $e) {
            report($e); // a mail problem must not hide the job's own result
        }
    }

    /** @return array<int,string> */
    private function recipients(): array
    {
        $configured = array_values(array_filter(array_map('trim', explode(',', (string) config('portal.alert_emails')))));
        if ($configured !== []) {
            return $configured;
        }

        try {
            return User::role(['super', 'admin'])->pluck('email')->filter()->unique()->values()->all();
        } catch (Throwable) {
            return [];
        }
    }
}
