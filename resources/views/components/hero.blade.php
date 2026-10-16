@props(['subtitle' => null])

@php
    $h = now()->hour;
    $greeting = $h < 12 ? 'Good morning' : ($h < 17 ? 'Good afternoon' : 'Good evening');
    $first = \Illuminate\Support\Str::of(auth()->user()?->name ?? '')->before(' ')->toString() ?: 'there';
@endphp

<div class="hero">
    <div>
        <h1 class="ttl-xl">{{ $greeting }}, {{ $first }}</h1>
        <p>{{ $subtitle }}</p>
        {{ $slot }}
    </div>
    <div class="hero-date">{{ now()->format('l, j F') }}</div>
</div>
