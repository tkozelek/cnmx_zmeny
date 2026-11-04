<x-layout title="Overenie e-mailu" description="Potvrď svoju e-mailovú adresu a dokonči registráciu do Cinemax Zmeny.">
    <div class="mx-auto flex w-full max-w-lg flex-1 flex-col justify-center px-4 py-12">
        <div class="rounded-lg border border-neutral-800 bg-neutral-900 p-6 shadow-sm sm:p-8">
            <h1 class="flex items-center gap-2.5 text-xl font-bold text-white">
                <i class="fa-solid fa-envelope-circle-check text-brand-400"></i>
                Over si e-mail
            </h1>

            <p class="mt-3 text-sm leading-relaxed text-neutral-300">
                Na adresu <strong class="text-neutral-100">{{ $unverifiedEmail }}</strong> sme poslali
                overovací odkaz. Klikni naň a&nbsp;vráť sa sem.
            </p>

            <p class="mt-2 text-xs leading-relaxed text-neutral-400">
                Po overení ťa ešte musí schváliť manažér kina. Ak e-mail nevidíš, pozri sa do spamu alebo si
                ho nechaj poslať znova.
            </p>

            @if(session('message'))
                <p class="mt-4 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-2.5 text-xs font-semibold text-emerald-400">
                    {{ session('message') }}
                </p>
            @endif

            <div class="mt-6 flex flex-wrap items-center gap-3">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <x-submit-button variant="custom" busy-label="Posielam…" class="min-h-11 rounded-xl border border-brand-500/60 bg-brand-500/10 px-4 text-sm text-brand-300 hover:bg-brand-500/20">
                        <i class="fa-solid fa-paper-plane mr-2 text-xs" aria-hidden="true"></i>Poslať znova
                    </x-submit-button>
                </form>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-submit-button variant="custom" busy-label="Odhlasujem…" class="min-h-11 rounded-xl border border-neutral-800 bg-neutral-900 px-4 text-sm text-neutral-300 hover:border-neutral-700 hover:text-white">
                        Odhlásiť sa
                    </x-submit-button>
                </form>
            </div>
        </div>
    </div>
</x-layout>
