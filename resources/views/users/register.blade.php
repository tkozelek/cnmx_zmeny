<x-layout>
    <div class="flex-1 min-h-full w-full py-6 md:py-10 flex items-center justify-center px-4 relative overflow-hidden bg-neutral-950">
        <!-- Ambient lighting glows -->

        <div class="absolute -top-40 -right-40 w-96 h-96 bg-sky-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-blue-600/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="w-full max-w-md relative z-10">
            <!-- Brand Badge / Logo -->
            <div class="text-center mb-5">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-neutral-900 border border-neutral-800 text-sky-400 shadow-xl shadow-black/40 text-xl mb-2">
                    <i class="fa-solid fa-user-plus"></i>
                </div>
                <h1 class="text-2xl font-black tracking-tight text-white uppercase">Vytvoriť účet</h1>
                <p class="text-neutral-400 text-xs mt-1">Pridajte sa k tímu a plánujte svoje zmeny</p>
            </div>

            <!-- Card container -->
            <div class="bg-neutral-900/90 backdrop-blur-md rounded-2xl border border-neutral-800 shadow-2xl shadow-black/60 p-6 space-y-4">
                <form class="space-y-3" action="{{ route('register.store') }}" method="POST">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <x-form-input
                            name="name"
                            label="Meno"
                            placeholder="Juraj"
                            :value="old('name')"
                            icon="fa-user"
                            required
                        />

                        <x-form-input
                            name="lastname"
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
                        label="E-mail"
                        placeholder="vas@email.com"
                        :value="old('email')"
                        icon="fa-envelope"
                        required
                    />

                    <!-- Team / Cinema Select Picker -->
                    <div class="relative">
                        <label for="team_id" class="block mb-1.5 text-xs font-semibold uppercase tracking-wider text-neutral-400">Kino / Prevádzka</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-neutral-500">
                                <i class="fa-solid fa-building-user"></i>
                            </div>
                            <select
                                name="team_id"
                                id="team_id"
                                required
                                class="w-full px-4 py-2.5 pl-11 rounded-xl bg-neutral-900 border border-neutral-800 text-white placeholder-neutral-500 transition-all duration-200 text-sm shadow-inner focus:outline-none focus:ring-2 focus:ring-sky-500/60 focus:border-sky-500 hover:border-neutral-700 appearance-none cursor-pointer"
                            >
                                @if(isset($teams) && $teams->count() > 0)
                                    @foreach($teams as $team)
                                        <option value="{{ $team->id }}" @selected(old('team_id') == $team->id || (empty(old('team_id')) && (str_contains(mb_strtolower($team->name), 'žilina') || str_contains(mb_strtolower($team->name), 'zilina'))))>
                                            {{ $team->name }}
                                        </option>
                                    @endforeach
                                @else
                                    <option value="">Kino Žilina</option>
                                @endif
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-neutral-500">
                                <i class="fa-solid fa-chevron-down text-xs"></i>
                            </div>
                        </div>
                        @error('team_id')
                            <p class="text-rose-400 text-xs mt-1 flex items-center gap-1 font-medium"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <x-form-input
                            type="password"
                            name="password"
                            label="Heslo"
                            placeholder="••••••••"
                            icon="fa-lock"
                            required
                        />

                        <x-form-input
                            type="password"
                            name="password_confirmation"
                            id="password_confirmation"
                            label="Potvrďte heslo"
                            placeholder="••••••••"
                            icon="fa-lock"
                            required
                        />
                    </div>

                    <button type="submit" class="w-full mt-1 py-3 px-5 bg-neutral-800 hover:bg-neutral-750 active:bg-neutral-850 text-white font-bold rounded-xl text-sm tracking-widest uppercase border border-neutral-700 hover:border-sky-500/50 shadow-lg shadow-black/40 transition duration-200 flex items-center justify-center gap-2 group">
                        <span>Registrovať sa</span>
                        <i class="fa-solid fa-arrow-right text-xs text-sky-400 group-hover:translate-x-1 transition-transform"></i>
                    </button>
                </form>

                <div class="pt-3 border-t border-neutral-800 text-center">
                    <p class="text-xs text-neutral-400">
                        Už máte vytvorený účet?
                        <a class="text-sky-400 hover:text-sky-300 font-semibold inline-flex items-center gap-1 ml-1 hover:underline" href="{{ route('login') }}">
                            Prihlásiť sa
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-layout>


