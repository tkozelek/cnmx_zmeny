<x-layout title="Registrácia" description="Vytvor si účet a pridaj sa k tímu Cinemax.">
    <div class="flex-1 min-h-full w-full py-6 md:py-10 flex items-center justify-center px-4 relative overflow-hidden bg-neutral-950">
        <!-- Ambient lighting glows -->

        <div class="absolute -top-40 -right-40 w-96 h-96 bg-brand-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-brand-700/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="w-full max-w-md relative z-10">
            <!-- Brand Badge / Logo -->
            <div class="text-center mb-5">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-lg bg-neutral-900 border border-neutral-800 text-brand-400 shadow-sm text-xl mb-2">
                    <i class="fa-solid fa-user-plus"></i>
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-white uppercase">Vytvoriť účet</h1>
                <p class="text-neutral-400 text-xs mt-1">Pridaj sa k tímu a plánuj svoje zmeny</p>
            </div>

            <!-- Card container -->
            <div class="bg-neutral-900 rounded-lg border border-neutral-800 shadow-sm p-6 space-y-4">
                <form class="space-y-3" action="{{ route('register.store') }}" method="POST">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <x-form-input
                            name="name"
                            autocomplete="given-name"
                            label="Meno"
                            placeholder="Juraj"
                            :value="old('name')"
                            icon="fa-user"
                            required
                        />

                        <x-form-input
                            name="lastname"
                            autocomplete="family-name"
                            label="Priezvisko"
                            placeholder="Hruška"
                            :value="old('lastname')"
                            icon="fa-user"
                            required
                        />
                    </div>

                    <x-form-input
                        type="email"
                        name="email"
                        autocomplete="email"
                        label="E-mail"
                        placeholder="tvoj@email.sk"
                        :value="old('email')"
                        icon="fa-envelope"
                        required
                    />

                    {{-- Several cinemas: nothing preselected, so nobody lands in the wrong one by accident. --}}
                    @php($singleTeam = isset($teams) && $teams->count() === 1 ? $teams->first() : null)
                    <div class="relative">
                        <label for="team_id" class="block mb-2 text-sm font-medium text-neutral-300">Kino</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-neutral-400">
                                <i class="fa-solid fa-building-user" aria-hidden="true"></i>
                            </div>
                            <select
                                name="team_id"
                                id="team_id"
                                required
                                @error('team_id') aria-invalid="true" aria-describedby="team_id-error" @enderror
                                class="w-full px-4 py-3 pl-11 rounded-xl bg-neutral-900 border border-neutral-500 text-white transition-all duration-200 text-base sm:text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/60 focus:border-brand-500 hover:border-neutral-400 appearance-none cursor-pointer"
                            >
                                @unless($singleTeam)
                                    <option value="" disabled @selected(empty(old('team_id')))>Vyber svoje kino</option>
                                @endunless
                                @foreach($teams ?? [] as $team)
                                    <option value="{{ $team->id }}" @selected(old('team_id') == $team->id || $singleTeam?->is($team))>{{ $team->name }}</option>
                                @endforeach
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-neutral-400">
                                <i class="fa-solid fa-chevron-down text-xs" aria-hidden="true"></i>
                            </div>
                        </div>
                        @error('team_id')
                            <p id="team_id-error" class="text-rose-400 text-xs mt-1.5 flex items-center gap-1 font-medium"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> {{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <x-form-input
                            type="password"
                            name="password"
                            autocomplete="new-password"
                            label="Heslo"
                            placeholder="••••••••"
                            icon="fa-lock"
                            required
                        />

                        <x-form-input
                            type="password"
                            name="password_confirmation"
                            autocomplete="new-password"
                            id="password_confirmation"
                            label="Potvrď heslo"
                            placeholder="••••••••"
                            icon="fa-lock"
                            required
                        />
                    </div>

                    <button type="submit" class="w-full mt-1 py-3 px-5 bg-neutral-800 hover:bg-neutral-750 active:bg-neutral-850 text-white font-bold rounded-xl text-sm tracking-widest uppercase border border-neutral-700 hover:border-brand-500/60 shadow-lg shadow-black/40 transition duration-200 flex items-center justify-center gap-2 group">
                        <span>Registrovať sa</span>
                        <i class="fa-solid fa-arrow-right text-xs text-brand-400 group-hover:translate-x-1 transition-transform"></i>
                    </button>

                    <p class="text-xs leading-relaxed text-neutral-400">
                        Meno, e-mail a zvolené kino použijem na vedenie tvojho účtu a plánovanie zmien. Uvidí ich manažment zvoleného kina.
                        <a href="{{ route('privacy') }}" class="font-semibold text-brand-400 hover:text-brand-300 hover:underline">Ochrana osobných údajov</a>
                    </p>
                </form>

                <div class="pt-3 border-t border-neutral-800 text-center">
                    <p class="text-xs text-neutral-400">
                        Už máš účet?
                        <a class="text-brand-400 hover:text-brand-300 font-semibold inline-flex items-center gap-1 ml-1 hover:underline" href="{{ route('login') }}">
                            Prihlásiť sa
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-layout>


