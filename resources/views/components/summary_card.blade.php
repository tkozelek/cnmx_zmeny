@props(['color' => 'blue', 'id'])

@php
    $bg = $color == 'blue' ? 'bg-blue-900/40' : 'bg-green-900/40';
    $text = $color == 'blue' ? 'text-blue-300' : 'text-green-300';
@endphp

<div {{ $attributes->merge(['class' => ' p-4 rounded-lg '.$bg]) }}>
    <p class="text-md {{ $text }}">{{ $slot }}</p>
    <div class="text-2xl font-bold {{ $text }}">
        <span id="{{ $id }}">0.00</span>
        <span class="text-base text-blue-300" id="{{ $id.'-add' }}"></span>
    </div>
</div>
