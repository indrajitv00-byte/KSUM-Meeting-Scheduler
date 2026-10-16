<?php

namespace App\Livewire\Meeting;

use App\Livewire\Support\MeetingActions;
use App\Livewire\Support\SlotFields;
use App\Models\Meeting;
use App\Services\MeetingService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Book a meeting')]
class MeetingsPage extends Component implements HasActions, HasForms
{
    use InteractsWithActions, InteractsWithForms;

    /** upcoming | past | other */
    public string $filter = 'upcoming';

    /** Outcome of the last booking request (shown above the list). */
    public ?array $result = null;

    /** The last submitted form, so a suggested slot can be booked with one click. */
    public ?array $lastRequest = null;

    /* ---------- booking dialog ---------- */

    public function scheduleAction(): Action
    {
        return Action::make('schedule')
            ->modalHeading('Request a Zoom meeting')
            ->modalDescription("We'll automatically find a free Zoom account for your slot.")
            ->modalSubmitActionLabel('Schedule meeting')
            ->modalWidth(Width::Large)
            ->fillForm(function () {
                $r = $this->lastRequest;
                $start = $r ? Carbon::parse($r['start_time']) : $this->defaultStart();
                $fresh = ! $r;
                if ($start->isPast()) {
                    $start = $this->defaultStart();
                    $fresh = true;
                }

                // a fresh form opens on a time that is actually free
                if ($fresh) {
                    $start = $this->firstFree($start, 60) ?? $start;
                }

                return [
                    'title' => $r['title'] ?? null,
                    'date' => $start->format('Y-m-d'),
                    'time' => $start->format('H:i'),
                    'duration_min' => (int) ($r['duration_min'] ?? 60),
                ];
            })
            ->schema([
                TextInput::make('title')
                    ->label('Meeting title')
                    ->placeholder('e.g. Project review')
                    ->autofocus()
                    ->required()->minLength(3)->maxLength(120),
                ...SlotFields::schema(),
            ])
            ->action(function (array $data, Action $action) {
                abort_unless(auth()->user()->can('meetings:create'), 403);

                // the form has separate date and time fields; the service takes one start time
                $data['start_time'] = $data['date'].' '.$data['time'].':00';
                unset($data['date'], $data['time']);

                // no department field in the form: use the one saved in the user's profile
                $data['department'] = auth()->user()->department;

                // on a problem keep the dialog open so nothing typed is lost
                if (! $this->book($data, fromDialog: true)) {
                    $action->halt();
                }
            });
    }

    /** One click on a suggested "free slot" chip. */
    public function pickSlot(string $iso): void
    {
        abort_unless(auth()->user()->can('meetings:create'), 403);

        if (! $this->lastRequest) {
            return;
        }

        $this->book(array_merge($this->lastRequest, ['start_time' => $iso]), fromDialog: false);
    }

    /** @return bool false when the dialog should stay open */
    private function book(array $data, bool $fromDialog): bool
    {
        $res = app(MeetingService::class)->schedule(auth()->user(), $data);

        if ($res['status'] === 'scheduled') {
            /** @var Meeting $m */
            $m = $res['meeting'];
            $this->lastRequest = null;
            $this->filter = 'upcoming';
            $this->result = [
                'type' => 'ok',
                'id' => $m->id,
                'title' => $m->title,
                'range' => $m->rangeLabel(),
                'join_url' => $m->join_url,
                'passcode' => $m->passcode,
                'invite' => $m->inviteText(),
                'start_url' => route('meetings.start', $m),
            ];
            Notification::make()->success()->title('Meeting scheduled')->send();

            return true;
        }

        if ($res['status'] === 'unavailable') {
            $this->lastRequest = $data;
            $this->result = [
                'type' => 'unavailable',
                'message' => $res['message'],
                'suggestions' => collect($res['suggestions'])->map(fn ($s) => [
                    'iso' => $s['start']->toIso8601String(),
                    'label' => $s['start']->format('D, j M Y, g:i A'),
                ])->all(),
            ];

            return true;
        }

        // error
        Notification::make()->danger()->title($res['error'])->send();
        if (! $fromDialog) {
            $this->result = ['type' => 'error', 'msg' => $res['error']];
        }

        return false;
    }

    /** First start time on or after $start (same day) where a Zoom host is free for $duration minutes. */
    private function firstFree(Carbon $start, int $duration): ?Carbon
    {
        foreach (app(MeetingService::class)->slotAvailability($start, $duration) as $hm => $isFree) {
            $t = Carbon::parse($start->toDateString().' '.$hm);
            if ($isFree && $t->gte($start)) {
                return $t;
            }
        }

        return null;
    }

    /** Next quarter hour at least an hour away, kept inside the working day. */
    private function defaultStart(): Carbon
    {
        $d = now()->addHour()->startOfMinute();
        $d = $d->copy()->addMinutes((15 - $d->minute % 15) % 15);

        $from = (int) config('portal.day_start_hour');
        $to = (int) config('portal.day_end_hour');

        if ($d->hour >= $to) {
            return $d->copy()->addDay()->setTime($from, 0);
        }

        return $d->hour < $from ? $d->copy()->setTime($from, 0) : $d;
    }

    /* ---------- per-meeting actions (meeting id passed as an argument) ---------- */

    public function cancelMeetingAction(): Action
    {
        return MeetingActions::cancel(function (int $id) {
            if (($this->result['id'] ?? null) === $id) {
                $this->result = null;
            }
        });
    }

    public function rescheduleMeetingAction(): Action
    {
        return MeetingActions::reschedule(function (int $id) {
            if (($this->result['id'] ?? null) === $id) {
                $this->result = null; // the old "scheduled" banner would show the previous time
            }
        });
    }

    public function recordingAction(): Action
    {
        return MeetingActions::recording();
    }

    /* ---------- page ---------- */

    public function render()
    {
        $all = Meeting::where('user_id', auth()->id())->orderByDesc('start_time')->limit(300)->get();

        $rows = match ($this->filter) {
            'past' => $all->filter(fn (Meeting $m) => $m->isFinished())->values(),
            'other' => $all->filter(fn (Meeting $m) => $m->status !== Meeting::SCHEDULED)->values(),
            default => $all->filter(fn (Meeting $m) => $m->isLive())->reverse()->values(), // soonest first
        };

        return view('livewire.meetings.meetings-page', ['meetings' => $rows]);
    }
}
