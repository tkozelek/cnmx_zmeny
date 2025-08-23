@props(['text'])
<div class="flex justify-between items-center">
    <span class="text-gray-400">{{ $text }}:</span>
    <span class="font-semibold text-white text-right">{{ $slot }}</span>
</div>
