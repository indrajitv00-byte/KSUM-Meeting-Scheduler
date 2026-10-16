<?php

namespace App\Livewire\Auth;

use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Layout;
use Livewire\Component;

class ForgotPage extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('email')->email()->required(),
        ])
        ->statePath('data')
        ;
    }

    public function login(){

        $data = $this->form->getState();

        $status = Password::sendResetLink(
            ["email" => $data['email']]
        );

        if($status === Password::RESET_LINK_SENT)
        {
            Notification::make()->success()->title('Reset Link Send')->send();
        }
        else{
            Notification::make()->danger()->title('Reset Failed')->send();
        }

    }

    #[Layout('components.layouts.blank')]
    public function render()
    {
        return view('livewire.auth.forgot');
    }
}
