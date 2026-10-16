<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;
use Throwable;

/** "Continue with Google" and "Continue with Startup Mission ID". */
class SocialLoginController extends Controller
{
    private function enabled(string $provider): bool
    {
        return in_array($provider, ['google', 'uid'], true) && filled(config("services.$provider.client_id"));
    }

    public function redirect(string $provider)
    {
        abort_unless($this->enabled($provider), 404);

        $driver = Socialite::driver($provider);
        if ($provider === 'google') {
            $driver->with(['prompt' => 'select_account']);
        }

        return $driver->redirect();
    }

    public function callback(string $provider)
    {
        abort_unless($this->enabled($provider), 404);

        try {
            $social = Socialite::driver($provider)->user();
        } catch (Throwable $e) {
            report($e);

            return $this->fail('Sign-in failed. Please try again.');
        }

        $email = Str::lower((string) $social->getEmail());
        if ($email === '') {
            return $this->fail('No email address was returned by the sign-in provider.');
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            $allowed = config('portal.allowed_domains');
            if ($allowed && ! in_array(Str::after($email, '@'), $allowed, true)) {
                return $this->fail('Accounts for this email domain are not allowed. Please use your organisation email.');
            }

            $user = User::create([
                'name' => $social->getName() ?: Str::before($email, '@'),
                'email' => $email,
                'password' => Str::random(40), // not used: they sign in with the provider (or "Forgot password")
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();

            $role = config('portal.default_role');
            if (Role::where('name', $role)->exists()) {
                $user->assignRole($role);
            } else {
                logger()->warning("Role '$role' does not exist - run `php artisan db:seed`.");
            }
        }

        Auth::login($user, true);
        request()->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    private function fail(string $message)
    {
        Notification::make()->danger()->title($message)->send();

        return redirect()->route('login');
    }
}
