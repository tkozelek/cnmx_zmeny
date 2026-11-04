<a
    href="{{ route($route) }}"
    @if($isMobile) @click="openn = false" @endif
    @if($isActive) aria-current="page" @endif
    @class([
        'transition-colors duration-150 flex items-center font-semibold',
        'w-full rounded-xl py-3 px-4 text-lg justify-center gap-3 hover:bg-neutral-800' => $isMobile,
        'px-4 py-2 text-base rounded-xl hover:bg-neutral-800 hover:text-white gap-2.5' => ! $isMobile,
        'text-neutral-200' => $isMobile && ! $isActive,
        'text-neutral-300' => ! $isMobile && ! $isActive,
        $activeClass => $isActive,
    ])
>

    @if($icon)
        <span class="opacity-80" aria-hidden="true">{!! $icon !!}</span>
    @endif

    <span>{{ $slot }}</span>
</a>
