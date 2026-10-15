<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

@include('layouts.partials._head')

<body class="min-h-screen flex flex-col bg-neutral-950 text-neutral-200 antialiased">

<a href="#obsah" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-3 focus:z-[200] focus:rounded-lg focus:bg-neutral-100 focus:px-4 focus:py-2.5 focus:text-sm focus:font-semibold focus:text-neutral-900">Preskočiť na obsah</a>

@include('layouts.partials._navigation')

<main id="obsah" tabindex="-1" class="flex-1 flex flex-col focus:outline-none">
    <div class="flex-1 flex flex-col">
        {{ $slot }}
    </div>
</main>

<footer class="border-t border-neutral-900 bg-neutral-950">
    <div class="container mx-auto flex flex-col items-center justify-between gap-2 px-4 py-6 text-sm text-neutral-400 sm:flex-row">
        <p>&copy; {{ now()->year }} {{ config('privacy.operator.name') }}</p>
        <nav aria-label="Pätička" class="flex gap-5">
            <a href="{{ route('help') }}" class="hover:text-white">Pomoc</a>
            <a href="{{ route('privacy') }}" class="hover:text-white">Ochrana osobných údajov</a>
        </nav>
    </div>
</footer>

    <x-flash-message />


    @livewireScripts
</body>

</html>
