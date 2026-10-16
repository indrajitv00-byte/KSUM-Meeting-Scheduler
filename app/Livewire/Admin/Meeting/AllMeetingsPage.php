<?php

namespace App\Livewire\Admin\Meeting;

use App\Livewire\Support\MeetingActions;
use App\Models\Meeting;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('All meetings')]
class AllMeetingsPage extends Component implements HasTable, HasForms, HasActions
{
    use InteractsWithTable, InteractsWithForms, InteractsWithActions;

    public function table(Table $table): Table
    {
        $canManage = fn () => auth()->user()->can('admin:meetings:update');

        return $table
            ->query(Meeting::query()->with(['user', 'zoomAccount']))
            ->heading('All meetings')
            ->defaultSort('start_time', 'desc')
            ->columns([
                TextColumn::make('start_time')
                    ->label('When')
                    ->dateTime('D, j M Y')
                    ->description(fn (Meeting $r) => $r->start_time->format('g:i A').' – '.$r->end_time->format('g:i A'))
                    ->sortable(),
                TextColumn::make('title')
                    ->searchable()
                    ->description(fn (Meeting $r) => $r->participants_est ? $r->participants_est.' participants' : null),
                TextColumn::make('user.name')
                    ->label('Requested by')
                    ->searchable()
                    ->description(fn (Meeting $r) => collect([$r->user?->organization, $r->user?->department])->filter()->implode(', ')),
                TextColumn::make('zoomAccount.label')->label('Account')->placeholder('—'),
                TextColumn::make('status')
                    ->badge()
                    ->state(fn (Meeting $r) => match (true) {
                        $r->status === Meeting::CANCELLED => 'Cancelled',
                        $r->status === Meeting::FAILED => 'No free account',
                        $r->isLive() => 'Scheduled',
                        default => 'Completed',
                    })
                    ->color(fn (string $state) => match ($state) {
                        'Scheduled' => 'success',
                        'No free account' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('view')
                    ->label('Show')
                    ->options([
                        'upcoming' => 'Upcoming',
                        'past' => 'Past',
                        'failed' => 'Not scheduled (no free account)',
                        'cancelled' => 'Cancelled',
                        'all' => 'Everything',
                    ])
                    ->default('upcoming')
                    ->selectablePlaceholder(false)
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? 'all') {
                        'upcoming' => $query->live(),
                        'past' => $query->finished(),
                        'failed' => $query->where('status', Meeting::FAILED),
                        'cancelled' => $query->where('status', Meeting::CANCELLED),
                        default => $query,
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->recordActions([
                Action::make('start')
                    ->label('Start')
                    ->url(fn (Meeting $record) => route('meetings.start', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (Meeting $record) => $record->isLive() && $canManage()),
                MeetingActions::reschedule()
                    ->visible(fn (Meeting $record) => $record->isLive() && $record->start_time->isFuture() && $canManage()),
                MeetingActions::cancel()
                    ->visible(fn (Meeting $record) => $record->isLive() && $canManage()),
                MeetingActions::recording()
                    ->visible(fn (Meeting $record) => $record->isFinished() && $canManage()),
            ])
            ->emptyStateHeading('Nothing to show');
    }

    public function render()
    {
        return view('livewire.admin.all-meetings-page');
    }
}
