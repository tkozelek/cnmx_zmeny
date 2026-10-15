<x-layout title="Zmena hesla" description="Zmeň si heslo do svojho účtu Cinemax Zmeny.">
    <div class="container mx-auto max-w-lg px-4 py-8">
        <div class="flex flex-col gap-6">
            <x-page-header icon="fa-key" title="Zmena hesla" subtitle="Zadaj svoje aktuálne a nové heslo pre účet" />

            {{-- Card container --}}
            <div class="bg-neutral-900 rounded-lg border border-neutral-800 shadow-sm p-6 sm:p-8 space-y-6">
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
                        autocomplete="current-password"
                        label="Staré heslo"
                        placeholder="Zadaj staré heslo"
                        icon="fa-lock"
                        required
                        autofocus
                    />

                    <x-form-input
                        type="password"
                        name="new_password"
                        autocomplete="new-password"
                        label="Nové heslo"
                        placeholder="Zadaj nové heslo"
                        icon="fa-key"
                        required
                    />

                    <x-form-input
                        type="password"
                        name="new_password_confirmation"
                        autocomplete="new-password"
                        label="Nové heslo znova"
                        placeholder="Zopakuj nové heslo"
                        icon="fa-check-double"
                        required
                    />

                    <button type="submit" class="w-full py-3.5 px-5 bg-neutral-800 hover:bg-neutral-750 active:bg-neutral-850 text-white font-bold rounded-xl text-sm tracking-widest uppercase border border-neutral-700 hover:border-brand-500/60 shadow-lg shadow-black/40 transition duration-200 flex items-center justify-center gap-2 group">
                        <span>Uložiť nové heslo</span>
                        <i class="fa-solid fa-check text-xs text-brand-400 group-hover:scale-110 transition-transform"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-layout>
