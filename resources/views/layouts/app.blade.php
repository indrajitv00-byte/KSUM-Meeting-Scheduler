<x-layouts.base :title="$title ?? null">
    <div class="portal">
        <aside class="topbar">
            <div class="topbar-in">
                <a class="logo" href="{{ route('home') }}">
                    <img class="logo-img" src="/img/logo.svg" alt="{{ config('app.name') }}">
                    <span class="brand-name">Meeting Portal</span>
                </a>

                <x-sidebar />

                <span class="spacer"></span>

                @auth
                    <div class="who">
                        <span class="avatar">{{ auth()->user()->initials() }}</span>
                        <div>
                            <b>{{ auth()->user()->name }}</b>
                            {{ auth()->user()->organization }}
                        </div>
                    </div>
                    <a class="btn btn-sm" href="/logout">Log out</a>
                @endauth
            </div>
        </aside>

        <main class="wrap">
            {{ $slot }}
        </main>

        <footer class="foot">
            <img src="/img/logo.svg" alt="">
            <span>&copy; {{ now()->year }} Kerala Startup Mission · {{ config('app.name') }}</span>
        </footer>
    </div>
</x-layouts.base>
