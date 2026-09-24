<x-layout
    title="Plánovanie zmien pre kino"
    description="Napíš si dni, kedy môžeš pracovať, a sleduj rozpis zmien v kine odkiaľkoľvek."
>
    {{-- Scroll progress hairline, driven by resources/js/welcome.js. --}}
    <div data-scroll-progress class="pointer-events-none fixed inset-x-0 top-0 z-50 h-0.5 origin-left scale-x-0 bg-brand-400" aria-hidden="true"></div>

    {{-- Hero Section --}}
    @include('welcome.partials._hero')

    {{-- How It Works Section --}}
    @include('welcome.partials._howitworks')

    {{-- Benefits Section --}}
    @include('welcome.partials._benefits')

    {{-- CTA / Register Section --}}
    @include('welcome.partials._cta')

    {{-- Footer --}}
    <footer class="border-t border-neutral-900 bg-neutral-950">
        <div class="container mx-auto px-4 py-6 text-center text-sm text-neutral-400">
            <p>&copy; {{ now()->year }} Cinemax Zmeny. Všetky práva vyhradené.</p>
        </div>
    </footer>
</x-layout>
