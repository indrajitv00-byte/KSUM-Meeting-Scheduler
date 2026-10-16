<?php

namespace App\Livewire\Support;

use App\Exceptions\MeetingException;
use App\Models\Meeting;
use App\Services\MeetingService;
use Closure;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Filament actions shared by the "My meetings" page (meeting id passed as an action argument)
 * and the admin "All meetings" table (meeting is the table row).
 */
class MeetingActions
{
    private static function resolve(?Meeting $record, array $arguments): Meeting
    {
        return $record ?? Meeting::findOrFail($arguments['meeting'] ?? 0);
    }

    /** @param Closure|null $after called with the cancelled meeting's id */
    public static function cancel(?Closure $after = null): Action
    {
        return Action::make('cancelMeeting')
            ->label('Cancel')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Cancel this meeting?')
            ->modalDescription('The Zoom link will stop working.')
            ->modalSubmitActionLabel('Yes, cancel meeting')
            ->action(function (array $arguments, ?Meeting $record = null) use ($after) {
                $meeting = self::resolve($record, $arguments);

                try {
                    app(MeetingService::class)->cancel($meeting, auth()->user());
                } catch (MeetingException $e) {
                    Notification::make()->danger()->title($e->getMessage())->send();

                    return;
                }

                Notification::make()->success()->title('Meeting cancelled')->send();
                if ($after) {
                    $after($meeting->id);
                }
            });
    }

    /**
     * Moves an upcoming meeting to another time. Keeps the same join link when the same host is free.
     *
     * @param  Closure|null  $after  called with the meeting's id after a successful move
     */
    public static function reschedule(?Closure $after = null): Action
    {
        $find = fn (?Meeting $record, array $arguments): ?Meeting => $record ?? Meeting::find($arguments['meeting'] ?? 0);

        return Action::make('rescheduleMeeting')
            ->label('Reschedule')
            ->modalHeading('Reschedule this meeting')
            ->modalDescription('Pick a new time. People who have the link will join at the new time.')
            ->modalSubmitActionLabel('Move meeting')
            ->modalWidth(Width::Large)
            ->fillForm(function (array $arguments, ?Meeting $record = null) use ($find) {
                $m = $find($record, $arguments);

                return $m ? [
                    'date' => $m->start_time->format('Y-m-d'),
                    'time' => $m->start_time->format('H:i'),
                    'duration_min' => (int) $m->start_time->diffInMinutes($m->end_time),
                ] : [];
            })
            ->schema(fn (array $arguments, ?Meeting $record = null) => SlotFields::schema($find($record, $arguments)))
            ->action(function (array $data, array $arguments, Action $action, ?Meeting $record = null) use ($find, $after) {
                $meeting = $find($record, $arguments);
                if (! $meeting) {
                    Notification::make()->danger()->title('Meeting not found.')->send();

                    return;
                }

                try {
                    $res = app(MeetingService::class)->reschedule(
                        $meeting,
                        auth()->user(),
                        $data['date'].' '.$data['time'].':00',
                        (int) $data['duration_min']
                    );
                } catch (MeetingException $e) {
                    Notification::make()->danger()->title($e->getMessage())->send();
                    $action->halt();

                    return;
                }

                if ($res['status'] === 'rescheduled') {
                    Notification::make()->success()
                        ->title('Meeting rescheduled')
                        ->body($res['link_changed']
                            ? 'A different Zoom host was free at the new time, so the join link changed. The new link was emailed to the organiser.'
                            : 'The join link stays the same.')
                        ->send();
                    if ($after) {
                        $after($meeting->id);
                    }

                    return;
                }

                if ($res['status'] === 'unavailable') {
                    $near = collect($res['suggestions'])->take(3)->map(fn ($s) => $s['start']->format('D j M, g:i A'))->implode(' · ');
                    Notification::make()->danger()
                        ->title('No free Zoom host at that time')
                        ->body($near !== '' ? 'Free nearby: '.$near : 'Try another day or a shorter length.')
                        ->send();
                } else {
                    Notification::make()->danger()->title($res['error'])->send();
                }

                $action->halt(); // keep the dialog open
            });
    }

    /** Lists the cloud-recording files of a finished meeting, each with a download link. */
    public static function recording(): Action
    {
        return Action::make('recording')
            ->label('Recording')
            ->modalHeading('Meeting recording')
            ->modalWidth(Width::Large)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->modalContent(function (array $arguments, ?Meeting $record = null) {
                $meeting = self::resolve($record, $arguments);
                $user = auth()->user();

                $data = ['files' => [], 'message' => null, 'error' => null];

                try {
                    // The modal re-renders on every Livewire request, so don't ask Zoom every time.
                    $data = Cache::remember(
                        "meeting-recording:{$meeting->id}:{$user->id}",
                        120,
                        fn () => app(MeetingService::class)->recordings($meeting, $user)
                    ) + ['error' => null];
                } catch (MeetingException $e) {
                    $data['error'] = $e->getMessage();
                } catch (Throwable $e) {
                    report($e);
                    $data['error'] = 'Could not load the recording. Please try again.';
                }

                return view('livewire.meetings.recording-list', $data + ['meeting' => $meeting]);
            });
    }
}
