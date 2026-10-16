<?php

namespace App\Livewire\Admin\Recording;

use App\Jobs\ArchiveMeetingRecordings;
use App\Livewire\Support\MeetingActions;
use App\Models\Meeting;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Where every finished meeting's recording stands in the nightly Zoom -> Google Drive copy. */
#[Title('Recordings')]
class RecordingsPage extends Component implements HasTable, HasForms, HasActions
{
    use InteractsWithTable, InteractsWithForms, InteractsWithActions;

    public const ARCHIVED = 'Archived';
    public const WAITING = 'Waiting';
    public const QUEUED = 'Retry queued';
    public const FAILED = 'Failed';
    public const NONE = 'No recording found';
    public const PURGED = 'Removed (retention)';
    public const UNCHECKED = 'Not checked';

    public static function statusOf(Meeting $m): string
    {
        $recent = $m->end_time->gte(now()->subDays((int) config('portal.recording_lookback_days')));

        return match (true) {
            $m->recordings_status === Meeting::REC_PURGED => self::PURGED,
            $m->recordings_status === Meeting::REC_ARCHIVED => self::ARCHIVED,
            $m->recordings_status === Meeting::REC_FAILED => self::FAILED,
            $m->recordings_status === Meeting::REC_QUEUED => self::QUEUED,
            $m->recordings_status === Meeting::REC_NONE => self::NONE,
            $m->recordings_status === Meeting::REC_PROCESSING => self::WAITING,
            $recent => self::WAITING,      // not looked at yet: tonight's run will
            default => self::UNCHECKED,    // older than the nightly job looks back
        };
    }

    public function table(Table $table): Table
    {
        $canManage = fn () => auth()->user()->can('admin:meetings:update');
        $recent = fn () => now()->subDays((int) config('portal.recording_lookback_days'));
        $months = (int) config('portal.recording_retention_months');

        return $table
            ->query(Meeting::query()
                ->scheduled()
                ->whereNotNull('zoom_meeting_id')
                ->where('end_time', '<=', now())
                ->with('user')
                ->withCount('recordings'))
            ->heading('Meeting recordings')
            ->description('Copied to Google Drive every night at '.config('portal.recording_sync_time').' and then removed from Zoom. '
                .($months > 0 ? "Drive copies are removed after {$months} months." : 'Drive copies are kept forever.'))
            ->defaultSort('start_time', 'desc')
            ->poll('15s')
            ->columns([
                TextColumn::make('start_time')
                    ->label('When')
                    ->dateTime('D, j M Y')
                    ->description(fn (Meeting $r) => $r->start_time->format('g:i A').' – '.$r->end_time->format('g:i A'))
                    ->sortable(),
                TextColumn::make('title')->searchable(),
                TextColumn::make('user.name')->label('Requested by')->searchable(),
                TextColumn::make('recordings_count')->label('Files in Drive')->alignCenter(),
                TextColumn::make('recordings_status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (Meeting $r) => self::statusOf($r))
                    ->color(fn (string $state) => match ($state) {
                        self::ARCHIVED => 'success',
                        self::FAILED => 'danger',
                        self::WAITING => 'warning',
                        self::QUEUED => 'info',
                        default => 'gray',
                    })
                    ->description(fn (Meeting $r) => $r->recordings_status === Meeting::REC_FAILED ? Str::limit((string) $r->recordings_error, 110) : null)
                    ->tooltip(fn (Meeting $r) => $r->recordings_status === Meeting::REC_FAILED ? $r->recordings_error : null)
                    ->wrap(),
                TextColumn::make('recordings_checked_at')->label('Last checked')->since()->placeholder('Not yet'),
            ])
            ->filters([
                SelectFilter::make('state')
                    ->label('Show')
                    ->options([
                        'attention' => 'Needs attention (failed / waiting)',
                        'failed' => 'Failed',
                        'waiting' => 'Waiting',
                        'archived' => 'Archived',
                        'none' => 'No recording found',
                        'purged' => 'Removed (retention)',
                        'all' => 'Everything',
                    ])
                    ->default('attention')
                    ->selectablePlaceholder(false)
                    ->query(function (Builder $query, array $data) use ($recent) {
                        $waiting = fn (Builder $q) => $q->whereNull('recordings_synced_at')->where(
                            fn ($w) => $w->whereIn('recordings_status', [Meeting::REC_PROCESSING, Meeting::REC_QUEUED])
                                ->orWhere(fn ($n) => $n->whereNull('recordings_status')->where('end_time', '>=', $recent()))
                        );

                        return match ($data['value'] ?? 'all') {
                            'attention' => $query->whereNull('recordings_synced_at')->where(
                                fn ($w) => $w->where('recordings_status', Meeting::REC_FAILED)
                                    ->orWhere(fn ($x) => $waiting($x))
                            ),
                            'failed' => $query->where('recordings_status', Meeting::REC_FAILED),
                            'waiting' => $waiting($query),
                            'archived' => $query->where('recordings_status', Meeting::REC_ARCHIVED),
                            'none' => $query->where('recordings_status', Meeting::REC_NONE),
                            'purged' => $query->where('recordings_status', Meeting::REC_PURGED),
                            default => $query,
                        };
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->recordActions([
                Action::make('retry')
                    ->label('Retry now')
                    ->requiresConfirmation()
                    ->modalHeading('Copy this recording now?')
                    ->modalDescription('It is copied to Google Drive in the background and removed from Zoom afterwards. Large files can take a while.')
                    ->modalSubmitActionLabel('Retry')
                    ->visible(fn (Meeting $record) => $canManage() && in_array(self::statusOf($record), [self::FAILED, self::WAITING, self::NONE, self::UNCHECKED], true))
                    ->action(function (Meeting $record) {
                        $record->update(['recordings_status' => Meeting::REC_QUEUED, 'recordings_error' => null]);
                        ArchiveMeetingRecordings::dispatch($record->id);

                        Notification::make()->success()
                            ->title('Retry queued')
                            ->body('The scheduler starts it within a minute. This list refreshes by itself.')
                            ->send();
                    }),
                MeetingActions::recording()
                    ->visible(fn (Meeting $record) => $canManage() && $record->recordings_status === Meeting::REC_ARCHIVED),
            ])
            ->emptyStateHeading('Nothing to show')
            ->emptyStateDescription('Finished meetings appear here once the nightly job has looked at them.');
    }

    public function render()
    {
        return view('livewire.admin.recordings-page');
    }
}
