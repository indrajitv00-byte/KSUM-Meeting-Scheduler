<?php

namespace App\Livewire\Admin\Role;

use App\Models\Role;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;

class RolePage extends Component implements HasTable, HasForms, HasActions
{
    use InteractsWithTable, InteractsWithForms, InteractsWithActions;

    public function table(Table $table): Table
    {
        return $table
            ->query(Role::query())
            ->heading('Roles')
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('type')->searchable()->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->model(Role::class)
                    ->schema([
                        Grid::make()->schema([
                            TextInput::make('name')->required(),
                            TextInput::make('type')->required(),
                          ]),
                        CheckboxList::make('permissions')->relationship('permissions', 'name')->columns(3),
                    ])
                    ->mutateDataUsing(function($data){
                        $data['guard_name'] = "web";
                        return $data;
                    })
                    ->createAnother(false)
                    ->visible(auth()->user()->can('admin:roles:create'))
            ])
            ->recordActions([
                EditAction::make()
                    ->schema(fn($record) => [
                        Grid::make()->schema([
                            TextInput::make('name')->required(),
                            TextInput::make('type')->required(),
                          ]),
                        CheckboxList::make('permissions')->relationship('permissions', 'name')->columns(3),
                    ])
                    ->visible(auth()->user()->can('admin:roles:update')),
                DeleteAction::make()->visible(auth()->user()->can('admin:roles:delete')),
            ])
        ;
    }

    public function render()
    {
        return view('livewire.base-page');
    }
}
