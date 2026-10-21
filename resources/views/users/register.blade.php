<x-layout title="Registrácia" description="Vytvor si účet a pridaj sa k tímu Cinemax.">
    <x-auth-card title="Vytvoriť účet" subtitle="Pridaj sa k tímu a plánuj svoje zmeny." wide>
        <form class="flex flex-col gap-6" action="{{ route('register.store') }}" method="POST">
            @csrf

            @if(session('error'))
                <x-alert>{{ session('error') }}</x-alert>
            @endif

            <div class="field-row">
                <x-form-input
                    name="name"
                    autocomplete="given-name"
                    label="Meno"
                    :value="old('name')"
                    icon="fa-user"
                    required
                />

                <x-form-input
                    name="lastname"
                    autocomplete="family-name"
                    label="Priezvisko"
                    :value="old('lastname')"
                    icon="fa-user"
                    required
                />
            </div>

            <x-form-input
                type="email"
                name="email"
                autocomplete="email"
                spellcheck="false"
                label="E-mail"
                hint="Pošleme ti naň odkaz na overenie účtu."
                placeholder="tvoj@email.sk"
                :value="old('email')"
                icon="fa-envelope"
                required
            />

            @if($showTeamPicker)
                <div class="field">
                    <label for="team_id" class="field__label text-sm font-medium text-neutral-200">Kino</label>
                    <p id="team_id-hint" class="field__hint text-sm text-neutral-400">Manažér tohto kina ťa potom schváli.</p>
                    <div class="field__control relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-neutral-400">
                            <i class="fa-solid fa-building-user" aria-hidden="true"></i>
                        </div>
                        <select
                            name="team_id"
                            id="team_id"
                            required
                            aria-describedby="team_id-hint @error('team_id') team_id-error @enderror"
                            @error('team_id') aria-invalid="true" @enderror
                            @class([
                                'min-h-12 w-full cursor-pointer appearance-none rounded-xl border bg-neutral-900 py-3 pl-11 pr-10 text-base text-white transition-all duration-200 hover:border-neutral-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/60 sm:text-sm',
                                'border-rose-500/70' => $errors->has('team_id'),
                                'border-neutral-500' => ! $errors->has('team_id'),
                            ])
                        >
                            {{-- Nothing preselected, so nobody lands in the wrong cinema by accident. --}}
                            <option value="" disabled @selected(empty(old('team_id')))>Vyber svoje kino</option>
                            @foreach($teams as $team)
                                <option value="{{ $team->id }}" @selected(old('team_id') == $team->id)>{{ $team->name }}</option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4 text-neutral-400">
                            <i class="fa-solid fa-chevron-down text-xs" aria-hidden="true"></i>
                        </div>
                    </div>
                    @error('team_id')
                        <p id="team_id-error" class="field__error flex items-start gap-1.5 text-sm font-medium text-rose-400">
                            <i class="fa-solid fa-circle-exclamation mt-0.5" aria-hidden="true"></i>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>
            @endif

            <div class="field-row">
                <x-form-input
                    type="password"
                    name="password"
                    autocomplete="new-password"
                    label="Heslo"
                    hint="Aspoň 8 znakov."
                    icon="fa-lock"
                    required
                />

                <x-form-input
                    type="password"
                    name="password_confirmation"
                    autocomplete="new-password"
                    id="password_confirmation"
                    label="Potvrď heslo"
                    hint="Ešte raz, nech sa nepomýliš."
                    icon="fa-lock"
                    enterkeyhint="go"
                    required
                />
            </div>

            <p class="text-sm leading-relaxed text-neutral-400">
                Meno, e-mail a zvolené kino použijem na vedenie tvojho účtu a plánovanie zmien. Uvidí ich manažment zvoleného kina.
                <a href="{{ route('privacy') }}" class="font-semibold text-brand-400 underline-offset-4 hover:text-brand-300 hover:underline">Ochrana osobných údajov</a>
            </p>

            <x-submit-button busy-label="Vytváram účet…">Vytvoriť účet</x-submit-button>
        </form>

        <x-slot:footer>
            Už máš účet?
            <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center px-1 font-semibold text-brand-400 underline-offset-4 hover:text-brand-300 hover:underline">Prihlásiť sa</a>
        </x-slot:footer>
    </x-auth-card>
</x-layout>
