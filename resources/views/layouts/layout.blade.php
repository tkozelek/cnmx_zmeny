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
    <div class="container mx-auto flex flex-col items-center justify-between gap-2 px-4 py-4 text-sm text-neutral-400 sm:flex-row md:px-6 lg:px-8">
        <p class="flex flex-wrap items-center justify-center gap-x-1.5">
            <span>&copy; {{ $currentYear }}</span>
            <span aria-hidden="true" class="text-neutral-700">&middot;</span>
            <a href="https://kozelek.sk" target="_blank" rel="noopener" class="group inline-flex min-h-10 items-center gap-1.5 px-1 text-neutral-300 hover:text-white">
                <span class="underline decoration-neutral-700 decoration-1 underline-offset-4 transition-colors group-hover:decoration-brand-400">{{ $operatorName }}</span>
                <i class="fa-solid fa-arrow-up-right-from-square text-[0.6rem] text-neutral-500 transition group-hover:-translate-y-px group-hover:translate-x-px group-hover:text-brand-400" aria-hidden="true"></i>
                <span class="sr-only">(kozelek.sk, otvorí sa v novom okne)</span>
            </a>
        </p>
        <nav aria-label="Pätička" class="flex gap-3">
            <a href="{{ route('help') }}" class="inline-flex min-h-10 items-center px-1 hover:text-white">Pomoc</a>
            <a href="{{ route('bug-report.create') }}" class="inline-flex min-h-10 items-center px-1 hover:text-white">Nahlásiť chybu</a>
            <a href="{{ route('privacy') }}" class="inline-flex min-h-10 items-center px-1 hover:text-white">Ochrana osobných údajov</a>
        </nav>
    </div>
</footer>

    <x-flash-message />


    @livewireScripts
</body>

</html>
