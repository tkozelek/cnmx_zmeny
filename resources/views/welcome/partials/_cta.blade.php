<section id="registracia" class="relative overflow-hidden bg-neutral-950 pt-12 sm:pt-16">
    <div class="container relative z-10 mx-auto px-4 text-center">
        <div class="reveal">
            <h2 class="mb-4 text-3xl font-bold text-neutral-100 md:text-4xl">
                <span class="split-word"><span class="split-word-inner">Poď na to</span></span>
            </h2>
            <p class="mx-auto mb-8 max-w-xl text-neutral-400">Zaregistruj sa alebo sa prihlás a zapíš si prvé zmeny.</p>
        </div>

        <div class="reveal mx-auto flex max-w-md flex-col gap-3 sm:flex-row" style="transition-delay: 150ms;">
            <a href="{{ route('login') }}" class="cta-button flex min-h-[48px] w-full items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-neutral-700 bg-neutral-900 px-6 py-3 font-semibold text-neutral-100 transition hover:border-neutral-600 hover:bg-neutral-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 focus-visible:ring-offset-2 focus-visible:ring-offset-neutral-950">
                <i class="fa-solid fa-right-to-bracket text-sm" aria-hidden="true"></i>
                Prihlásiť sa
            </a>
            <a href="{{ route('register') }}" class="cta-button flex min-h-[48px] w-full items-center justify-center gap-2 whitespace-nowrap rounded-lg bg-neutral-100 px-6 py-3 font-semibold text-neutral-900 transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 focus-visible:ring-offset-2 focus-visible:ring-offset-neutral-950">
                <i class="fa-solid fa-user-plus text-sm" aria-hidden="true"></i>
                Vytvoriť účet
            </a>
        </div>

        <p class="mt-12 text-xs font-medium uppercase tracking-[0.2em] text-neutral-500" aria-hidden="true">
            Vyber si miesto
        </p>
    </div>

    {{-- Interactive auditorium, see resources/js/seat-field.js. Purely decorative, so it is
         hidden from assistive tech; the real actions are the buttons above. --}}
    <canvas data-seat-field class="mt-2 block h-[300px] w-full touch-pan-y sm:h-[380px] lg:h-[440px]" aria-hidden="true"></canvas>
</section>
