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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as RulesPassword;

class ResetPage extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    #[Url]
    public $token;

    #[Url]
    public $email;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('password')->password()->required()->revealable()->confirmed()->rule(RulesPassword::min(8)->mixedCase()->letters()->numbers()->symbols()),
            TextInput::make('password_confirmation')->password()->required(),
        ])
        ->statePath('data')
        ;
    }

    public function login(){

        $data = $this->form->getState();

        $status = Password::reset(
            [
                "email" => $this->email,
                "password" => $data['password'],
                "password_confirmation" => $data['password_confirmation'],
                "token" => $this->token
            ],
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();
            }
        );

        if($status == Password::PASSWORD_RESET){
            Notification::make()->success()->title('Reset Successfull')->send();

            Auth::logoutOtherDevices($data['password']);

            return redirect()->route('login');
        }
        else{
            Notification::make()->danger()->title('Reset Failed')->send();
        }

    }

    #[Layout('components.layouts.blank')]
    public function render()
    {
        return view('livewire.auth.reset');
    }
}
