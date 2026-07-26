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
</x-layout>



