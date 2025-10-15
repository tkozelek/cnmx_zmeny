@props(['color' => 'blue', 'id'])

@php
    $bg = $color == 'blue' ? 'bg-blue-900/40' : 'bg-green-900/40';
    $text = $color == 'blue' ? 'text-blue-300' : 'text-green-300';
@endphp

<div {{ $attributes->merge(['class' => ' p-4 rounded-lg '.$bg]) }}>
    <p class="text-md {{ $text }}">{{ $slot }}</p>
    <p id="{{ $id }}" class="text-2xl font-bold {{ $text }}">0.00</p>
</div>
