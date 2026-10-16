<div>
    <x-hero subtitle="Manage Zoom accounts, schedules and meetings in one place." />

    <div class="stats">
        @foreach ($stats as [$n, $label])
            <div class="stat"><b>{{ $n }}</b><span>{{ $label }}</span></div>
        @endforeach
    </div>

    <section class="card">
        <h2 class="ttl">Account usage today</h2>
        <p class="muted">Share of working hours ({{ $hours }}) booked on each account.</p>

        @forelse ($usage as $u)
            <div style="margin:.8rem 0">
                <div style="display:flex;justify-content:space-between"><b>{{ $u['label'] }}</b><span class="muted">{{ $u['pct'] }}%</span></div>
                <div class="bar"><i style="width:{{ $u['pct'] }}%"></i></div>
            </div>
        @empty
            <p class="muted">Add a Zoom account to get started.</p>
        @endforelse
    </section>
</div>
