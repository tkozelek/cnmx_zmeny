@props(['color' => 'blue', 'id', 'value' => '0.00'])

@php
    [$background, $foreground] = match ($color) {
        'green' => ['bg-green-900/40', 'text-green-300'],
        'amber' => ['bg-amber-900/40', 'text-amber-300'],
        'rose' => ['bg-rose-900/40', 'text-rose-300'],
        default => ['bg-blue-900/40', 'text-blue-300'],
    };
@endphp

<div {{ $attributes->merge(['class' => "rounded-lg p-4 {$background}"]) }}>
    <p class="text-md {{ $foreground }}">{{ $slot }}</p>
    <p id="{{ $id }}" class="text-2xl font-bold {{ $foreground }}">{{ $value }}</p>
</div>
