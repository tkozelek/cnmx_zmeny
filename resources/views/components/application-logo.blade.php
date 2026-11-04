@props(['isGuest' => true])

<a href="{{ route($isGuest ? 'welcome.index' : 'calendar.index') }}" class="text-xl font-bold tracking-wider uppercase text-white focus:outline-none focus:shadow-outline">
    CINEMAX
</a>
