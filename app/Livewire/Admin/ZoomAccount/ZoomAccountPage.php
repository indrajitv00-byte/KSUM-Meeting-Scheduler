<?php

namespace App\Livewire\Admin\ZoomAccount;

use App\Exceptions\MeetingException;
use App\Models\ZoomAccount;
use App\Services\MeetingService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Title('Zoom accounts')]
class ZoomAccountPage extends Component implements HasTable, HasForms, HasActions
{
    use InteractsWithTable, InteractsWithForms, InteractsWithActions;

    public function table(Table $table): Table
    {
        $cannotEdit = fn () => ! auth()->user()->can('admin:zoom-accounts:update');

        return $table
            ->query(ZoomAccount::query()->withCount(['meetings as upcoming_count' => fn ($q) => $q->live()]))
            ->heading('Zoom accounts')
            ->description('Meetings are scheduled on the lowest priority number first. Disable an account to stop using it.')
            ->defaultSort('priority')
            ->columns([
                TextColumn::make('label')->searchable()->sortable(),
                TextColumn::make('host_email')->label('Host email')->searchable(),
                TextInputColumn::make('priority')
                    ->type('number')
                    ->rules(['required', 'integer', 'min:1'])
                    ->sortable()
                    ->disabled($cannotEdit),
                TextColumn::make('upcoming_count')->label('Upcoming'),
                ToggleColumn::make('is_active')->label('Active')->disabled($cannotEdit),
            ])
            ->headerActions([
                Action::make('addHost')
                    ->label('Add a host')
                    ->modalHeading('Add a host')
                    ->modalDescription("All hosts use the organisation's Zoom credentials from the .env file. The host email must be a licensed user on that Zoom account; it is verified with Zoom when you add it.")
                    ->modalSubmitActionLabel('Verify & add host')
                    ->modalWidth(Width::Large)
                    ->fillForm(['priority' => 100])
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 2])->schema([
                            TextInput::make('label')->placeholder('e.g. IT Dept Zoom')->required(),
                            TextInput::make('host_email')->label('Host email')->email()->placeholder('host@organization.com')->required(),
                        ]),
                        TextInput::make('priority')->numeric()->minValue(1)->default(100)->required(),
                    ])
                    ->action(function (array $data, Action $action) {
                        try {
                            app(MeetingService::class)->addAccount(auth()->user(), $data['label'], $data['host_email'], (int) $data['priority']);
                        } catch (MeetingException $e) {
                            Notification::make()->danger()->title($e->getMessage())->send();
                            $action->halt();
                        } catch (Throwable $e) {
                            report($e);
                            Notification::make()->danger()->title('Could not add the host. Please try again.')->send();
                            $action->halt();
                        }

                        Notification::make()->success()->title('Host added')->send();
                    })
                    ->visible(fn () => auth()->user()->can('admin:zoom-accounts:create')),
            ])
            ->emptyStateHeading('No Zoom accounts yet')
            ->emptyStateDescription('Add a host to get started.');
    }

    public function render()
    {
        return view('livewire.admin.zoom-account-page');
    }
}
