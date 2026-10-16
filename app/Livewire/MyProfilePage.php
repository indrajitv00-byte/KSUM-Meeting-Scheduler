<?php

namespace App\Livewire;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Title;
use Livewire\Component;

/** "My profile": every signed-in user can edit their own details and change their password. */
#[Title('My profile')]
class MyProfilePage extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $profileData = [];

    public ?array $passwordData = [];

    public function mount(): void
    {
        $user = auth()->user();

        $this->profileForm->fill([
            'name' => $user->name,
            'email' => $user->email,
            'organization' => $user->organization,
            'department' => $user->department,
        ]);
        $this->passwordForm->fill();
    }

    public function profileForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['default' => 1, 'sm' => 2])->schema([
                    TextInput::make('name')->label('Full name')->required()->minLength(2)->maxLength(120),
                    TextInput::make('email')
                        ->label('Email')
                        ->disabled()->dehydrated(false)
                        ->helperText('This is your sign-in email. Ask an admin if it needs to change.'),
                    TextInput::make('organization')
                        ->label('Organisation')
                        ->placeholder('e.g. Kerala Startup Mission')
                        ->maxLength(120),
                    TextInput::make('department')
                        ->placeholder('e.g. IT')
                        ->helperText('Pre-filled when you book a meeting.')
                        ->maxLength(120),
                ]),
            ])
            ->statePath('profileData');
    }

    public function passwordForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('current_password')
                    ->label('Current password')
                    ->password()->revealable()->required()
                    ->autocomplete('current-password')
                    ->helperText('Always sign in with Google and never set a password? Log out and use "Forgot your password?" on the sign-in page.'),
                Grid::make(['default' => 1, 'sm' => 2])->schema([
                    TextInput::make('password')
                        ->label('New password')
                        ->password()->revealable()->required()->confirmed()
                        ->rule(Password::min(8)->mixedCase()->letters()->numbers()->symbols())
                        ->autocomplete('new-password')
                        ->helperText('At least 8 characters, with upper and lower case letters, a number and a symbol.'),
                    TextInput::make('password_confirmation')
                        ->label('Confirm new password')
                        ->password()->revealable()->required()
                        ->autocomplete('new-password'),
                ]),
            ])
            ->statePath('passwordData');
    }

    public function saveProfile(): void
    {
        $data = $this->profileForm->getState();

        auth()->user()->update([
            'name' => trim($data['name']),
            'organization' => filled($data['organization'] ?? null) ? trim($data['organization']) : null,
            'department' => filled($data['department'] ?? null) ? trim($data['department']) : null,
        ]);

        Notification::make()->success()->title('Profile saved')->send();
    }

    public function changePassword(): void
    {
        $user = auth()->user();

        $key = 'password-change:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            Notification::make()->danger()
                ->title('Too many attempts')
                ->body('Please try again in '.RateLimiter::availableIn($key).' seconds.')
                ->send();

            return;
        }

        $data = $this->passwordForm->getState();

        if (! Hash::check($data['current_password'], $user->password)) {
            RateLimiter::hit($key, 300);
            Notification::make()->danger()->title('Your current password is not correct')->send();

            return;
        }

        if (Hash::check($data['password'], $user->password)) {
            Notification::make()->danger()->title('Choose a password you have not used just now')->send();

            return;
        }

        $user->forceFill(['password' => $data['password']])->save(); // hashed by the model cast
        RateLimiter::clear($key);

        // sign out "remember me" logins on other devices; this one stays signed in
        Auth::logoutOtherDevices($data['password']);

        $this->passwordForm->fill();
        Notification::make()->success()->title('Password changed')->send();
    }

    public function render()
    {
        return view('livewire.my-profile-page');
    }
}
