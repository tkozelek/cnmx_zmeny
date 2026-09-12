@props(['isMobile' => false, 'icon' => null])

<form method="POST" action="{{ route('logout') }}" class="{{ $isMobile ? 'w-full' : '' }}">
    @csrf
    <button
        type="submit"
        @class([
            'flex items-center font-medium transition-colors duration-150',
            'w-full rounded-lg py-3 px-4 text-lg justify-center gap-3 text-rose-400 hover:bg-rose-500/10' => $isMobile,
            'w-full px-3 py-2 text-sm rounded-lg text-rose-400 hover:bg-rose-500/10 hover:text-rose-300 gap-2.5 text-left' => ! $isMobile,
        ])
    >
        @if($icon)
            <span class="opacity-80">{!! $icon !!}</span>
        @endif
        <span>{{ $slot }}</span>
    </button>
</form>
