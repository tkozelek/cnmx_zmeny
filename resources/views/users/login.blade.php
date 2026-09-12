<x-layout>
    <div class="flex-1 min-h-full w-full py-12 md:py-20 flex items-center justify-center px-4 relative overflow-hidden bg-neutral-950">
        <!-- Ambient lighting glows -->

        <div class="absolute -top-40 -left-40 w-96 h-96 bg-sky-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-blue-600/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="w-full max-w-md relative z-10">
            <!-- Brand Badge / Logo -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-neutral-900 border border-neutral-800 text-sky-400 shadow-xl shadow-black/40 text-2xl mb-4">
                    <i class="fa-solid fa-film"></i>
                </div>
                <h1 class="text-3xl font-black tracking-tight text-white uppercase">Vitajte späť</h1>
                <p class="text-neutral-400 text-sm mt-2">Prihláste sa do portálu pracovných zmien CNMX</p>
            </div>

            <!-- Card container -->
            <div class="bg-neutral-900/90 backdrop-blur-md rounded-2xl border border-neutral-800 shadow-2xl shadow-black/60 p-8 space-y-6">
                @if(session('error'))
                    <x-alert>{{ session('error') }}</x-alert>
                @endif

                <form class="space-y-5" action="{{ route('login.auth') }}" method="POST">
                    @csrf

                    <x-form-input
                        type="email"
                        name="email"
                        label="E-mailová adresa"
                        placeholder="vas@email.com"
                        :value="old('email')"
                        icon="fa-envelope"
                        required
                        autofocus
                    />

                    <x-form-input
                        type="password"
                        name="password"
                        label="Heslo"
                        placeholder="••••••••"
                        icon="fa-lock"
                        required
                    />

                    <div class="flex items-center justify-between pt-1">
                        <label class="inline-flex items-center cursor-pointer">
                            <input id="remember" name="remember" type="checkbox" class="w-4 h-4 text-sky-500 border-neutral-700 rounded bg-neutral-950 focus:ring-2 focus:ring-sky-500/50 focus:ring-offset-0 focus:ring-offset-neutral-900 cursor-pointer">
                            <span class="ml-2.5 text-xs text-neutral-400 hover:text-neutral-300 font-medium">Zapamätať si ma</span>
                        </label>

                        <a href="{{ route('password.index') }}" class="text-xs font-semibold text-sky-400 hover:text-sky-300 transition duration-150">
                            Zabudnuté heslo?
                        </a>
                    </div>

                    <button type="submit" class="w-full py-3.5 px-5 bg-neutral-800 hover:bg-neutral-750 active:bg-neutral-850 text-white font-bold rounded-xl text-sm tracking-widest uppercase border border-neutral-700 hover:border-sky-500/50 shadow-lg shadow-black/40 transition duration-200 flex items-center justify-center gap-2 group">
                        <span>Prihlásiť sa</span>
                        <i class="fa-solid fa-arrow-right text-xs text-sky-400 group-hover:translate-x-1 transition-transform"></i>
                    </button>
                </form>

                <div class="pt-4 border-t border-neutral-800 text-center">
                    <p class="text-xs text-neutral-400">
                        Nemáte ešte účet?
                        <a class="text-sky-400 hover:text-sky-300 font-semibold inline-flex items-center gap-1 ml-1 hover:underline" href="{{ route('register') }}">
                            Vytvoriť nový účet
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>

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
                class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80 backdrop-blur-md overflow-y-auto"
            >
                <div @click.away="show = false" class="relative w-full max-w-md rounded-2xl border border-neutral-800 bg-neutral-900 p-6 sm:p-7 shadow-2xl text-center space-y-4 my-auto">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-sky-500/10 text-sky-400 border border-sky-500/20 text-2xl mx-auto">
                        <i class="fa-solid fa-envelope-circle-check"></i>
                    </div>

                    <h3 class="text-xl font-bold text-white">Overovací e-mail bol odoslaný</h3>

                    <p class="text-sm text-neutral-300 leading-relaxed">
                        Ďakujeme za registráciu! Na adresu <strong class="text-white">{{ session('registered_email') }}</strong> sme poslali overovací odkaz.
                    </p>

                    <p class="text-xs text-neutral-400 leading-relaxed">
                        Pred prihlásením prosím klikni na odkaz v e-maile a potvrď svoj účet. Následne ťa schváli vedúci kina.
                    </p>

                    <div class="pt-3 border-t border-neutral-800 flex flex-col sm:flex-row items-center justify-center gap-3">
                        <form method="POST" action="{{ route('verification.resend_guest') }}" class="w-full sm:w-auto">
                            @csrf
                            <input type="hidden" name="email" value="{{ session('registered_email') }}">
                            <button type="submit" class="w-full sm:w-auto px-4 py-2.5 text-xs font-semibold rounded-xl border border-neutral-700 bg-neutral-800 hover:bg-neutral-700 text-neutral-200 transition">
                                <i class="fa-solid fa-paper-plane mr-1 text-xs"></i> Poslať znova
                            </button>
                        </form>

                        <button @click="show = false" type="button" class="w-full sm:w-auto px-5 py-2.5 text-xs font-bold rounded-xl text-neutral-900 bg-neutral-100 hover:bg-white transition shadow-md">
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
                class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80 backdrop-blur-md overflow-y-auto"
            >
                <div @click.away="show = false" class="relative w-full max-w-md rounded-2xl border border-neutral-800 bg-neutral-900 p-6 sm:p-7 shadow-2xl text-center space-y-4 my-auto">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/20 text-2xl mx-auto">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>

                    <h3 class="text-xl font-bold text-white">Účet čaká na overenie e-mailu</h3>

                    <p class="text-sm text-neutral-300 leading-relaxed">
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
                                <i class="fa-solid fa-paper-plane mr-1 text-xs"></i> Poslať overovací e-mail znova
                            </button>
                        </form>

                        <button @click="show = false" type="button" class="w-full sm:w-auto px-5 py-2.5 text-xs font-bold rounded-xl text-neutral-900 bg-neutral-100 hover:bg-white transition shadow-md">
                            Zavrieť
                        </button>
                    </div>
                </div>
            </div>
        </template>
    @endif
</x-layout>



