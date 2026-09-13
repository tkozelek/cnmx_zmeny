@props(['icon' => null, 'title', 'subtitle' => null])

<div class="flex flex-col gap-4 border-b border-neutral-800 pb-5 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="flex items-center gap-2.5 text-2xl font-bold text-white">
            @if($icon)
                <i class="fa-solid {{ $icon }} text-sky-400"></i>
            @endif
            {{ $title }}
        </h1>

        @if($subtitle)
            <p class="mt-1 text-xs text-neutral-400">{{ $subtitle }}</p>
        @endif
    </div>

    @if(trim($slot))
        <div class="flex items-center gap-2">
            {{ $slot }}
        </div>
    @endif
</div>
