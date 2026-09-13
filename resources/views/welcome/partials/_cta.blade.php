<section id="registracia" class="bg-neutral-950 py-20 sm:py-28">
    <div class="container mx-auto px-4 text-center">
        <div class="reveal">
            <h2 class="mb-4 text-3xl font-bold text-neutral-100 md:text-4xl">Pripravení pridať sa?</h2>
            <p class="mx-auto mb-8 max-w-xl text-neutral-400">Vytvorte si účet alebo sa prihláste a začnite plánovať svoje zmeny ešte dnes.</p>
        </div>

        <div class="reveal mx-auto max-w-2xl rounded-2xl border border-neutral-800 bg-neutral-900 p-8 shadow-2xl" style="transition-delay: 150ms;">
            <div class="flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('login') }}" class="cta-button flex w-full items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-neutral-700 bg-neutral-800 px-6 py-3 font-semibold text-neutral-100 transition hover:border-neutral-600 hover:bg-neutral-700">
                    <i class="fa-solid fa-right-to-bracket text-sm"></i>
                    Prihlásiť sa
                </a>
                <a href="{{ route('register') }}" class="cta-button flex w-full items-center justify-center gap-2 whitespace-nowrap rounded-lg bg-neutral-100 px-6 py-3 font-semibold text-neutral-900 transition hover:bg-white">
                    <i class="fa-solid fa-user-plus text-sm"></i>
                    Vytvoriť účet
                </a>
            </div>
        </div>
    </div>
</section>
