<?php

namespace App\Livewire\Auth;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

class LoginPage extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('email')->email()->required()->autocomplete('username'),
            TextInput::make('password')
                ->hint(str('[Forgot your password?](/password/forgot)')->inlineMarkdown()->toHtmlString())
                ->password()->required()->revealable()->autocomplete('current-password'),
        ])
        ->statePath('data');
    }

    public function login()
    {
        $data = $this->form->getState();

        $key = 'login:'.Str::lower($data['email']).'|'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            Notification::make()->danger()
                ->title('Too many attempts')
                ->body('Please try again in '.RateLimiter::availableIn($key).' seconds.')
                ->send();

            return;
        }

        if (Auth::attempt(['email' => $data['email'], 'password' => $data['password']], true)) {
            RateLimiter::clear($key);
            request()->session()->regenerate();

            return redirect()->intended(route('home'));
        }

        RateLimiter::hit($key, 60);
        Notification::make()->danger()->title('Invalid Credentials')->send();
    }

    #[Layout('components.layouts.blank')]
    public function render()
    {
        // Sign-in buttons that are configured in .env
        $providers = array_filter([
            'google' => filled(config('services.google.client_id')) ? 'Continue with Google' : null,
            'uid' => filled(config('services.uid.client_id')) ? 'Continue with Startup Mission ID' : null,
        ]);

        return view('livewire.auth.login', ['providers' => $providers]);
    }
}
