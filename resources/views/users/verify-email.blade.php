<x-layout title="Overenie e-mailu">
    <div class="mx-auto flex w-full max-w-lg flex-1 flex-col justify-center px-4 py-12">
        <div class="rounded-2xl border border-neutral-800 bg-neutral-900 p-6 shadow-xl sm:p-8">
            <h1 class="flex items-center gap-2.5 text-xl font-bold text-white">
                <i class="fa-solid fa-envelope-circle-check text-sky-400"></i>
                Over si e-mail
            </h1>

            <p class="mt-3 text-sm leading-relaxed text-neutral-300">
                Na adresu <strong class="text-neutral-100">{{ auth()->user()?->email ?? session('unverified_email', 'tvoj e-mail') }}</strong> sme poslali
                overovací odkaz. Klikni naň a vráť sa sem.
            </p>

            <p class="mt-2 text-xs leading-relaxed text-neutral-500">
                Po overení ťa ešte musí schváliť vedúci kina. Ak e-mail nevidíš, pozri sa do spamu alebo si
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
                    <button type="submit"
                            class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-sky-500/40 bg-sky-500/10 px-4 text-sm font-semibold text-sky-300 transition hover:bg-sky-500/20">
                        <i class="fa-solid fa-paper-plane text-xs"></i>
                        Poslať znova
                    </button>
                </form>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-neutral-800 bg-neutral-900 px-4 text-sm font-semibold text-neutral-300 transition hover:border-neutral-700 hover:text-white">
                        Odhlásiť sa
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-layout>
