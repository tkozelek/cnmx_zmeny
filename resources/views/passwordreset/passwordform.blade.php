<x-layout title="Resetovanie hesla" description="Nastavte si nové heslo do účtu Cine-max Zmeny.">
    <div class="flex-1 min-h-full w-full py-12 md:py-20 flex items-center justify-center px-4 relative overflow-hidden bg-neutral-950">
        {{-- Ambient lighting glows --}}
        <div class="absolute -top-40 -left-40 w-96 h-96 bg-brand-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-blue-600/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="w-full max-w-md relative z-10">
            {{-- Brand Badge / Logo --}}
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-lg bg-neutral-900 border border-neutral-800 text-brand-400 shadow-sm text-xl mb-3">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-white uppercase">Resetovanie hesla</h1>
                <p class="text-neutral-400 text-sm mt-1">Zadajte svoje nové heslo pre účet</p>
            </div>

            {{-- Card container --}}
            <div class="bg-neutral-900 rounded-lg border border-neutral-800 shadow-sm p-6 sm:p-8 space-y-6">
                @if(session('error'))
                    <x-alert>{{ session('error') }}</x-alert>
                @endif

                <form class="space-y-5" action="{{ route('password.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <x-form-input
                        type="email"
                        name="email"
                        label="E-mailová adresa"
                        placeholder="vas@email.com"
                        :value="$email ?: old('email')"
                        icon="fa-envelope"
                        required
                    />

                    <x-form-input
                        type="password"
                        name="password"
                        label="Nové heslo"
                        placeholder="Zadajte nové heslo"
                        icon="fa-key"
                        required
                        autofocus
                    />

                    <x-form-input
                        type="password"
                        name="password_confirmation"
                        label="Nové heslo znova"
                        placeholder="Zopakujte nové heslo"
                        icon="fa-check-double"
                        required
                    />

                    <button type="submit" class="w-full py-3.5 px-5 bg-neutral-800 hover:bg-neutral-750 active:bg-neutral-850 text-white font-bold rounded-xl text-sm tracking-widest uppercase border border-neutral-700 hover:border-brand-500/60 shadow-lg shadow-black/40 transition duration-200 flex items-center justify-center gap-2 group">
                        <span>Resetovať heslo</span>
                        <i class="fa-solid fa-check text-xs text-brand-400 group-hover:scale-110 transition-transform"></i>
                    </button>
                </form>

                <div class="pt-4 border-t border-neutral-800 text-center">
                    <a class="text-xs text-neutral-400 hover:text-slate-300 font-semibold inline-flex items-center gap-1.5 transition duration-150" href="{{ route('login') }}">
                        <i class="fa-solid fa-arrow-left text-[10px] text-brand-400"></i> Späť na prihlásenie
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-layout>
