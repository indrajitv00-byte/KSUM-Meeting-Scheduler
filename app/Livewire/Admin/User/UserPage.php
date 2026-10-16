<?php

namespace App\Livewire\Admin\User;

use App\Models\User;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Livewire\Component;

class UserPage extends Component implements HasTable, HasForms, HasActions
{
    use InteractsWithTable, InteractsWithForms, InteractsWithActions;

    public function table(Table $table): Table
    {
        return $table
            ->query(User::query())
            ->heading('Users')
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->label('Name'),
                TextColumn::make('email')->searchable()->sortable(),
                TextColumn::make('organization')->searchable()->toggleable(),
                TextColumn::make('department')->searchable()->toggleable(),
                TextColumn::make('roles.name')->badge()->searchable()
            ])
            ->headerActions([
                CreateAction::make()
                    ->model(User::class)
                    ->schema([
                        Grid::make()->schema([
                            TextInput::make('name')->required(),
                            TextInput::make('email')->required()->email()->unique(),
                            TextInput::make('organization'),
                            TextInput::make('department'),
                            Select::make('roles')->required()->multiple()->relationship('roles', 'name')->preload(),
                        ])
                    ])
                    ->mutateDataUsing(function($data){
                        // nobody knows this password: the person signs in with Google / Startup Mission ID,
                        // or uses "Forgot your password?" on the login page
                        $data['password'] = Str::random(40);
                        return $data;
                    })
                    ->createAnother(false)
                    ->visible(auth()->user()->can('admin:users:create'))
            ])
            ->recordActions([
                EditAction::make()
                    ->schema(fn($record) => [
                        Grid::make()->schema([
                            TextInput::make('name')->required(),
                            TextInput::make('email')->required()->email()->unique(ignorable: $record),
                            TextInput::make('organization'),
                            TextInput::make('department'),
                            Select::make('roles')->required()->multiple()->relationship('roles', 'name')->preload(),
                        ])
                    ])
                    ->visible(auth()->user()->can('admin:users:update')),
                DeleteAction::make()->visible(auth()->user()->can('admin:users:delete')),
            ])
        ;
    }

    public function render()
    {
        return view('livewire.base-page');
    }
}
