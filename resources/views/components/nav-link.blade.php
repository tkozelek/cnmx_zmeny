@props(['route', 'icon' => null, 'isMobile' => false, 'activeClass' => 'bg-neutral-800 text-white'])

@php
    // "calendar.index" is also active on "calendar.show" (another week), and so on.
    $isActive = request()->routeIs($route, Str::beforeLast($route, '.').'.*');
@endphp

<a
    href="{{ route($route) }}"
    @if($isMobile) @click="openn = false" @endif
    @if($isActive) aria-current="page" @endif
    @class([
        'transition-colors duration-150 flex items-center font-semibold',
        'w-full rounded-xl py-3 px-4 text-lg justify-center gap-3 text-neutral-200 hover:bg-neutral-800' => $isMobile,
        'px-4 py-2 text-base rounded-xl text-neutral-300 hover:bg-neutral-800 hover:text-white gap-2.5' => ! $isMobile,
        $activeClass => $isActive,
    ])
>

    @if($icon)
        <span class="opacity-80" aria-hidden="true">{!! $icon !!}</span>
    @endif

    <span>{{ $slot }}</span>
</a>
