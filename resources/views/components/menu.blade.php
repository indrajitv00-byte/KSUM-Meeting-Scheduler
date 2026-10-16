@props([
  'label',
  'route' => "#",
  'permissions' => null,
  'icon' => null,
])

@php
    $active = Route::has($route) && Route::is($route);
@endphp

@if (! $permissions || auth()->user()?->can($permissions))
    <a href="{{ Route::has($route) ? route($route) : $route }}" class="{{ $active ? 'active' : '' }}">
        @if ($icon)
            <svg viewBox="0 0 24 24" aria-hidden="true">{!! $icon !!}</svg>
        @endif
        {{ $label }}
    </a>
@endif
