<x-layout title="Prihlásenie" description="Prihlás sa do portálu pracovných zmien Cinemax.">
    <x-auth-card title="Vitaj späť" subtitle="Prihlás sa do portálu pracovných zmien Cinemax.">
        <form class="flex flex-col gap-6" action="{{ route('login.auth') }}" method="POST">
            @csrf

            @if(session('error'))
                <x-alert>{{ session('error') }}</x-alert>
            @endif

            <x-form-input
                type="email"
                name="email"
                autocomplete="username"
                spellcheck="false"
                label="E-mail"
                placeholder="tvoj@email.sk"
                :value="old('email')"
                icon="fa-envelope"
                required
                autofocus
            />

            <x-form-input
                type="password"
                name="password"
                autocomplete="current-password"
                label="Heslo"
                icon="fa-lock"
                enterkeyhint="go"
                required
            />

            <div class="-my-2 flex flex-wrap items-center justify-between gap-x-4">
                <label class="flex min-h-11 cursor-pointer items-center gap-3 text-sm text-neutral-300">
                    <input id="remember" name="remember" type="checkbox" class="size-5 cursor-pointer rounded border-neutral-600 bg-neutral-950 text-brand-500 focus:ring-2 focus:ring-brand-500/60 focus:ring-offset-0">
                    Zapamätať si ma
                </label>

                <a href="{{ route('password.index') }}" class="inline-flex min-h-11 items-center text-sm font-medium text-brand-400 underline-offset-4 hover:text-brand-300 hover:underline">
                    Zabudnuté heslo?
                </a>
            </div>

            <x-submit-button busy-label="Prihlasujem…">Prihlásiť sa</x-submit-button>
        </form>

        <x-slot:footer>
            Ešte nemáš účet?
            <a href="{{ route('register') }}" class="inline-flex min-h-11 items-center px-1 font-semibold text-brand-400 underline-offset-4 hover:text-brand-300 hover:underline">Vytvoriť účet</a>
        </x-slot:footer>
    </x-auth-card>

    {{-- Registration Success Modal --}}
    @if(session('show_registration_modal'))
        <template x-teleport="body">
            <div
                x-data="{ show: true }"
                x-cloak
                x-show="show"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                x-trap.inert.noscroll="show"
                @keydown.escape.window="show = false"
                role="dialog"
                aria-modal="true"
                aria-labelledby="registered-title"
                aria-describedby="registered-desc"
                class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80 backdrop-blur-md overflow-y-auto"
            >
                <div @click.away="show = false" class="relative w-full max-w-md rounded-lg border border-neutral-800 bg-neutral-900 p-6 sm:p-7 shadow-xl text-center space-y-4 my-auto">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-brand-500/10 text-brand-400 border border-brand-500/60 text-2xl mx-auto">
                        <i class="fa-solid fa-envelope-circle-check" aria-hidden="true"></i>
                    </div>

                    <h2 id="registered-title" class="text-xl font-bold text-white">Overovací e-mail bol odoslaný</h2>

                    <p id="registered-desc" class="text-sm text-neutral-300 leading-relaxed">
                        Ďakujeme za registráciu! Na adresu <strong class="text-white">{{ session('registered_email') }}</strong> sme poslali overovací odkaz.
                    </p>

                    <p class="text-xs text-neutral-400 leading-relaxed">
                        Pred prihlásením prosím klikni na odkaz v e-maile a potvrď svoj účet. Potom ťa ešte schváli manažér kina.
                    </p>

                    <div class="pt-3 border-t border-neutral-800 flex flex-col sm:flex-row items-center justify-center gap-3">
                        <form method="POST" action="{{ route('verification.resend_guest') }}" class="w-full sm:w-auto">
                            @csrf
                            <input type="hidden" name="email" value="{{ session('registered_email') }}">
                            <button type="submit" class="w-full sm:w-auto px-4 py-2.5 text-xs font-semibold rounded-xl border border-neutral-700 bg-neutral-800 hover:bg-neutral-700 text-neutral-200 transition">
                                <i class="fa-solid fa-paper-plane mr-1 text-xs" aria-hidden="true"></i> Poslať znova
                            </button>
                        </form>

                        <button @click="show = false" type="button" autofocus class="w-full sm:w-auto px-5 py-2.5 text-xs font-bold rounded-xl text-neutral-900 bg-neutral-100 hover:bg-white transition shadow-md">
                            Rozumiem
                        </button>
                    </div>
                </div>
            </div>
        </template>
    @endif

    {{-- Unverified Email Modal --}}
    @if(session('show_unverified_modal'))
        <template x-teleport="body">
            <div
                x-data="{ show: true }"
                x-cloak
                x-show="show"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                x-trap.inert.noscroll="show"
                @keydown.escape.window="show = false"
                role="dialog"
                aria-modal="true"
                aria-labelledby="unverified-title"
                aria-describedby="unverified-desc"
                class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80 backdrop-blur-md overflow-y-auto"
            >
                <div @click.away="show = false" class="relative w-full max-w-md rounded-lg border border-neutral-800 bg-neutral-900 p-6 sm:p-7 shadow-xl text-center space-y-4 my-auto">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/20 text-2xl mx-auto">
                        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                    </div>

                    <h2 id="unverified-title" class="text-xl font-bold text-white">Účet čaká na overenie e-mailu</h2>

                    <p id="unverified-desc" class="text-sm text-neutral-300 leading-relaxed">
                        Tvoja e-mailová adresa <strong class="text-white">{{ session('unverified_email') }}</strong> ešte nebola overená.
                    </p>

                    <p class="text-xs text-neutral-400 leading-relaxed">
                        Pred prihlásením je potrebné kliknúť na overovací odkaz v e-maile. Ak e-mail nevidíš, pozri sa do spamu.
                    </p>

                    <div class="pt-3 border-t border-neutral-800 flex flex-col sm:flex-row items-center justify-center gap-3">
                        <form method="POST" action="{{ route('verification.resend_guest') }}" class="w-full sm:w-auto">
                            @csrf
                            <input type="hidden" name="email" value="{{ session('unverified_email') }}">
                            <button type="submit" class="w-full sm:w-auto px-4 py-2.5 text-xs font-semibold rounded-xl border border-amber-500/30 bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 transition">
                                <i class="fa-solid fa-paper-plane mr-1 text-xs" aria-hidden="true"></i> Poslať overovací e-mail znova
                            </button>
                        </form>

                        <button @click="show = false" type="button" autofocus class="w-full sm:w-auto px-5 py-2.5 text-xs font-bold rounded-xl text-neutral-900 bg-neutral-100 hover:bg-white transition shadow-md">
                            Zavrieť
                        </button>
                    </div>
                </div>
            </div>
        </template>
    @endif
</x-layout>
