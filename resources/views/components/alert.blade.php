{{-- An error that stays on the page, for things the user has to act on - a failed login, a
     blocked account. The toast in <x-flash-message> is for confirmations: it sits bottom-right
     and vanishes after three seconds, which is the wrong place and the wrong lifetime for
     "your password was wrong". --}}
@props(['type' => 'error'])

@php
    $icon = match ($type) {
        'success' => 'fa-circle-check',
        'warning' => 'fa-triangle-exclamation',
        default => 'fa-circle-exclamation',
    };
@endphp

<div role="alert" @class([
    'rounded-lg border px-4 py-3 text-sm',
    'border-red-500/40 bg-red-500/10 text-red-300' => $type === 'error',
    'border-emerald-500/40 bg-emerald-500/10 text-emerald-300' => $type === 'success',
    'border-amber-500/40 bg-amber-500/10 text-amber-300' => $type === 'warning',
])>
    <div class="flex items-start gap-2">
        <i class="fa-solid {{ $icon }} mt-0.5"></i>
        <div class="space-y-1">{{ $slot }}</div>
    </div>
</div>
