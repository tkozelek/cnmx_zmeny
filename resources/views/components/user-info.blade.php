@props(['text', 'icon' => null])
<div class="flex items-center justify-between py-2.5 border-b border-neutral-800/80 last:border-0 text-sm">
    <span class="flex items-center gap-2 text-neutral-400">
        @if($icon)
            <i class="{{ $icon }} w-4 text-xs text-neutral-500"></i>
        @endif
        {{ $text }}:
    </span>
    <span class="font-medium text-neutral-200 text-right truncate pl-2">{{ $slot }}</span>
</div>
