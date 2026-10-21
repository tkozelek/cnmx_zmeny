<x-layout
    title="Plánovanie zmien pre kino"
    description="Zapíš sa na dni, kedy môžeš pracovať v kine, pozri sa, kto ide s tebou, a rozpis maj vždy v mobile."
>
    {{-- Scroll progress hairline, driven by resources/js/welcome.js. --}}
    <div data-scroll-progress class="pointer-events-none fixed inset-x-0 top-0 z-50 h-0.5 origin-left scale-x-0 bg-brand-400" aria-hidden="true"></div>

    {{-- Hero Section --}}
    @include('welcome.partials._hero')

    {{-- Week board scene --}}
    @include('welcome.partials._week')

    {{-- Registration steps, CTA and auditorium --}}
    @include('welcome.partials._cta')

</x-layout>
