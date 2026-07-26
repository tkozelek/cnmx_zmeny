<x-layout title="ZMENA HESLA">
    <div class="flex-1 min-h-full w-full py-12 md:py-20 flex items-center justify-center px-4 relative overflow-hidden bg-neutral-950">
        {{-- Ambient lighting glows --}}
        <div class="absolute -top-40 -left-40 w-96 h-96 bg-sky-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-blue-600/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="w-full max-w-md relative z-10">
            {{-- Brand Badge / Icon --}}
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-neutral-900 border border-neutral-800 text-sky-400 shadow-xl shadow-black/40 text-2xl mb-4">
                    <i class="fa-solid fa-key"></i>
                </div>
                <h1 class="text-3xl font-black tracking-tight text-white uppercase">Zmena hesla</h1>
                <p class="text-neutral-400 text-sm mt-2">Zadajte svoje aktuálne a nové heslo pre účet</p>
            </div>

            {{-- Card container --}}
            <div class="bg-neutral-900/90 backdrop-blur-md rounded-2xl border border-neutral-800 shadow-2xl shadow-black/60 p-8 space-y-6">
                @if(session('error'))
                    <x-alert>{{ session('error') }}</x-alert>
                @endif
                @if(session('message'))
                    <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm font-medium">
                        {{ session('message') }}
                    </div>
                @endif

                <form class="space-y-5" action="{{ route('settings.password.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <x-form-input
                        type="password"
                        name="current_password"
                        label="Staré heslo"
                        placeholder="Zadajte staré heslo"
                        icon="fa-lock"
                        required
                        autofocus
                    />

                    <x-form-input
                        type="password"
                        name="new_password"
                        label="Nové heslo"
                        placeholder="Zadajte nové heslo"
                        icon="fa-key"
                        required
                    />

                    <x-form-input
                        type="password"
                        name="new_password_confirmation"
                        label="Nové heslo znova"
                        placeholder="Zopakujte nové heslo"
                        icon="fa-check-double"
                        required
                    />

                    <button type="submit" class="w-full py-3.5 px-5 bg-neutral-800 hover:bg-neutral-750 active:bg-neutral-850 text-white font-bold rounded-xl text-sm tracking-widest uppercase border border-neutral-700 hover:border-sky-500/50 shadow-lg shadow-black/40 transition duration-200 flex items-center justify-center gap-2 group">
                        <span>Uložiť nové heslo</span>
                        <i class="fa-solid fa-check text-xs text-sky-400 group-hover:scale-110 transition-transform"></i>
                    </button>
                </form>

                <div class="pt-4 border-t border-neutral-800 text-center">
                    <a class="text-xs text-neutral-400 hover:text-slate-300 font-semibold inline-flex items-center gap-1.5 transition duration-150" href="{{ route('welcome.index') }}">
                        <i class="fa-solid fa-arrow-left text-[10px] text-sky-400"></i> Späť na prehľad
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-layout>
