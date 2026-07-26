<x-layout>
    <div class="flex-1 min-h-full w-full py-12 md:py-20 flex items-center justify-center px-4 relative overflow-hidden bg-neutral-950">
        <!-- Ambient lighting glows -->

        <div class="absolute -top-40 -left-40 w-96 h-96 bg-sky-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-blue-600/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="w-full max-w-md relative z-10">
            <!-- Brand Badge / Logo -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-neutral-900 border border-neutral-800 text-sky-400 shadow-xl shadow-black/40 text-2xl mb-4">
                    <i class="fa-solid fa-key"></i>
                </div>
                <h1 class="text-3xl font-black tracking-tight text-white uppercase">Zabudnuté heslo</h1>
                <p class="text-neutral-400 text-sm mt-2">Zadajte svoj e-mail pre zaslanie inštrukcií na obnovu</p>
            </div>

            <!-- Card container -->
            <div class="bg-neutral-900/90 backdrop-blur-md rounded-2xl border border-neutral-800 shadow-2xl shadow-black/60 p-8 space-y-6">
                <form class="space-y-5" action="{{ route('password.send') }}" method="POST">
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

                    <button type="submit" class="w-full py-3.5 px-5 bg-neutral-800 hover:bg-neutral-750 active:bg-neutral-850 text-white font-bold rounded-xl text-sm tracking-widest uppercase border border-neutral-700 hover:border-sky-500/50 shadow-lg shadow-black/40 transition duration-200 flex items-center justify-center gap-2 group">
                        <span>Odoslať inštrukcie</span>
                        <i class="fa-solid fa-paper-plane text-xs text-sky-400 group-hover:translate-x-1 transition-transform"></i>
                    </button>
                </form>

                <div class="pt-4 border-t border-neutral-800 text-center">
                    <a class="text-xs text-neutral-400 hover:text-slate-300 font-semibold inline-flex items-center gap-1.5 transition duration-150" href="{{ route('login') }}">
                        <i class="fa-solid fa-arrow-left text-[10px] text-sky-400"></i> Späť na prihlásenie
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-layout>



