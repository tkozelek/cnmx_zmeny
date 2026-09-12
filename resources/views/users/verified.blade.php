<x-layout title="E-mail overený">
    <div class="flex-1 min-h-full w-full py-12 md:py-20 flex items-center justify-center px-4 relative overflow-hidden bg-neutral-950">
        <!-- Ambient lighting glows -->
        <div class="absolute -top-40 -left-40 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-sky-600/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="w-full max-w-lg relative z-10">
            <div class="bg-neutral-900/90 backdrop-blur-md rounded-2xl border border-neutral-800 shadow-2xl shadow-black/60 p-8 sm:p-10 space-y-6 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-3xl mx-auto shadow-lg">
                    <i class="fa-solid fa-circle-check"></i>
                </div>

                <div class="space-y-2">
                    <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-white">E-mail bol úspešne overený</h1>
                    <p class="text-sm sm:text-base text-neutral-300 leading-relaxed">
                        Tvoja e-mailová adresa bola potvrdená. Tvoj účet teraz čaká na schválenie vedúcim kina (manažérom).
                    </p>
                </div>

                <div class="p-4 rounded-xl bg-neutral-950/60 border border-neutral-800 text-left space-y-2">
                    <div class="flex items-center gap-2 text-sky-400 font-semibold text-xs uppercase tracking-wider">
                        <i class="fa-solid fa-user-clock text-xs"></i>
                        <span>Čo nasleduje ďalej?</span>
                    </div>
                    <p class="text-xs text-neutral-400 leading-relaxed">
                        Manažér kina čoskoro skontroluje tvoju registráciu a schváli tvoj prístup. Akonáhle bude tvoj účet schválený, dostaneš potvrdzujúci e-mail a budeš sa môcť prihlásiť do systému.
                    </p>
                </div>

                <div class="pt-2">
                    <a
                        href="{{ route('login') }}"
                        class="w-full py-3.5 px-5 bg-neutral-100 hover:bg-white text-neutral-900 font-bold rounded-xl text-sm transition shadow-lg shadow-black/40 flex items-center justify-center gap-2 group"
                    >
                        <span>Prejsť na prihlásenie</span>
                        <i class="fa-solid fa-arrow-right text-xs text-neutral-900 group-hover:translate-x-1 transition-transform"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-layout>
