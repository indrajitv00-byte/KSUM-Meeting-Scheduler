<div class="auth">
    <div class="auth-hero">
        <div>
            <h1>Schedule Zoom meetings in seconds</h1>
            <p>Request a slot and the portal automatically finds a free Zoom account for you.</p>
            <ul>
                <li>Instant join and host links</li>
                <li>Automatic free-account matching</li>
                <li>Email confirmations and reminders</li>
            </ul>
        </div>
    </div>

    <div class="auth-card">
        <div class="logo-plain">
            <img src="/img/logo.svg" alt="{{ config('app.name') }}">
        </div>

        <h2 class="ttl">Welcome back</h2>
        <p class="muted" style="margin-bottom:1.25rem">Log in to schedule Zoom meetings.</p>

        @foreach ($providers as $key => $label)
            <a href="{{ route('social.redirect', $key) }}" class="btn btn-block" style="margin-top:.6rem">
                @if ($key === 'google')
                    <svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true">
                        <path fill="#EA4335" d="M24 9.5c3.5 0 6.6 1.2 9.1 3.6l6.8-6.8C35.9 2.4 30.4 0 24 0 14.6 0 6.5 5.4 2.6 13.2l7.9 6.1C12.4 13.6 17.7 9.5 24 9.5z"/>
                        <path fill="#4285F4" d="M46.5 24.5c0-1.6-.1-3.1-.4-4.5H24v9h12.7c-.6 3-2.3 5.5-4.8 7.2l7.5 5.8c4.4-4.1 7.1-10.1 7.1-17.5z"/>
                        <path fill="#FBBC05" d="M10.5 28.7c-.5-1.4-.8-3-.8-4.7s.3-3.2.8-4.7l-7.9-6.1C.9 16.4 0 20.1 0 24s.9 7.6 2.6 10.8l7.9-6.1z"/>
                        <path fill="#34A853" d="M24 48c6.5 0 11.9-2.1 15.9-5.8l-7.5-5.8c-2.1 1.4-4.8 2.3-8.4 2.3-6.3 0-11.6-4.1-13.5-9.8l-7.9 6.1C6.5 42.6 14.6 48 24 48z"/>
                    </svg>
                @endif
                {{ $label }}
            </a>
        @endforeach

        @if (count($providers))
            <div class="or"><span>or log in with email</span></div>
        @endif

        <form wire:submit="login">
            {{ $this->form }}

            <div class="mt-5">
                <x-filament::button type="submit" class="w-full" size="lg">
                    Log in
                </x-filament::button>
            </div>
        </form>
    </div>
</div>
