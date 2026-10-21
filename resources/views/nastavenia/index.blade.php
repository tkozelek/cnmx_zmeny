<x-layout title="Nastavenia" description="Tvoje nastavenia v portáli pracovných zmien Cinemax.">
    <div class="container mx-auto max-w-lg px-4 py-8">
        <div class="flex flex-col gap-6">
            <x-page-header icon="fa-sliders" title="Nastavenia" subtitle="Tvoj účet a ako sa ti má portál zobrazovať." />

            <x-settings-section id="profil" title="Profil" description="Takto ťa uvidia manažéri a kolegovia v rozpise.">
                <form action="{{ route('settings.name.update') }}" method="POST" class="flex flex-col gap-5">
                    @csrf
                    @method('PUT')

                    <x-form-input name="name" label="Meno" icon="fa-user" autocomplete="given-name" :value="old('name', $user->name)" required />
                    <x-form-input name="lastname" label="Priezvisko" icon="fa-user" autocomplete="family-name" :value="old('lastname', $user->lastname)" required />

                    <x-submit-button busy-label="Ukladám…" class="sm:w-auto sm:self-start">Uložiť meno</x-submit-button>
                </form>
            </x-settings-section>

            <x-settings-section id="email" title="E-mailová adresa" description="Zmenu najprv potvrdíš zo súčasnej adresy, potom overíš novú. Dovtedy sa prihlasuješ súčasnou.">
                <p class="text-sm text-neutral-300">
                    Súčasná adresa: <span class="break-all font-semibold text-white">{{ $user->email }}</span>
                </p>

                @if($user->pending_email)
                    <div class="flex flex-col gap-3 rounded-xl border border-amber-500/30 bg-amber-500/10 p-4 text-sm text-amber-100 sm:flex-row sm:items-center sm:justify-between">
                        <p>
                            Zmena na <strong class="break-all">{{ $user->pending_email }}</strong> čaká na potvrdenie.
                            @if($user->pending_email_approved_at)
                                Otvor overovací odkaz, ktorý sme poslali na novú adresu.
                            @else
                                Najprv ju potvrď odkazom, ktorý sme poslali na súčasnú adresu.
                            @endif
                        </p>
                        <form action="{{ route('settings.email.destroy') }}" method="POST" class="shrink-0">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex min-h-11 items-center rounded-lg border border-amber-500/40 px-4 text-sm font-semibold text-amber-100 transition hover:bg-amber-500/20">
                                Zrušiť zmenu
                            </button>
                        </form>
                    </div>
                @endif

                <form action="{{ route('settings.email.update') }}" method="POST" class="flex flex-col gap-5">
                    @csrf
                    @method('PUT')

                    <x-form-input type="email" name="email" id="new-email" label="Nová e-mailová adresa" icon="fa-envelope" autocomplete="email" :value="old('email')" required />
                    <x-form-input type="password" name="password" id="email-password" label="Aktuálne heslo" icon="fa-lock" autocomplete="current-password" required />

                    <x-submit-button busy-label="Odosielam…" class="sm:w-auto sm:self-start">Zmeniť e-mail</x-submit-button>
                </form>
            </x-settings-section>

            <x-settings-section id="zobrazenie" title="Týždeň na mobile" description="Na tablete a počítači sa týždeň vždy zobrazí ako karty vedľa seba.">
                <form action="{{ route('settings.update') }}" method="POST" class="flex flex-col gap-5">
                    @csrf
                    @method('PUT')

                    <fieldset class="flex flex-col gap-3">
                        <legend class="sr-only">Týždeň na mobile</legend>

                        @foreach([
                            ['value' => 0, 'title' => 'Karty', 'text' => 'Každý deň ako samostatná karta s tlačidlom navrchu.'],
                            ['value' => 1, 'title' => 'Kompaktný zoznam', 'text' => 'Celý týždeň na jednej obrazovke – deň, mená a tlačidlo v jednom riadku.'],
                        ] as $option)
                            <label class="flex cursor-pointer items-start gap-4 rounded-xl border border-neutral-700 bg-neutral-950 p-4 transition hover:border-neutral-500 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-950/40">
                                <input type="radio" name="compact_week" value="{{ $option['value'] }}" @checked((int) old('compact_week', (int) $user->compact_week) === $option['value'])
                                       class="mt-1 size-5 shrink-0 cursor-pointer border-neutral-500 bg-neutral-900 text-brand-500 focus:ring-0">
                                <span class="flex min-w-0 flex-1 flex-col gap-1">
                                    <span class="font-semibold text-neutral-100">{{ $option['title'] }}</span>
                                    <span class="text-sm text-neutral-400">{{ $option['text'] }}</span>
                                </span>
                                {{-- A thumbnail of the layout, so the choice is obvious before saving. --}}
                                <span class="flex w-14 shrink-0 flex-col gap-1 pt-1" aria-hidden="true">
                                    @if($option['value'])
                                        @foreach(range(1, 5) as $row)
                                            <span class="flex items-center gap-1 rounded-sm bg-neutral-800 p-1">
                                                <span class="h-1.5 w-3 rounded-sm bg-neutral-500"></span>
                                                <span class="h-1 flex-1 rounded-sm bg-neutral-600"></span>
                                                <span class="h-2 w-3.5 rounded-sm {{ $row === 2 ? 'bg-emerald-600' : 'bg-brand-600' }}"></span>
                                            </span>
                                        @endforeach
                                    @else
                                        @foreach(range(1, 2) as $card)
                                            <span class="flex flex-col overflow-hidden rounded-sm bg-neutral-800">
                                                <span class="h-2 {{ $card === 1 ? 'bg-emerald-600' : 'bg-brand-600' }}"></span>
                                                <span class="mx-auto my-1 h-1.5 w-6 rounded-sm bg-neutral-500"></span>
                                                <span class="mx-1 mb-1 h-1 rounded-sm bg-neutral-600"></span>
                                                <span class="mx-1 mb-1.5 h-1 rounded-sm bg-neutral-600"></span>
                                            </span>
                                        @endforeach
                                    @endif
                                </span>
                            </label>
                        @endforeach

                        @error('compact_week')
                            <p class="text-sm font-medium text-rose-400">{{ $message }}</p>
                        @enderror
                    </fieldset>

                    <x-submit-button busy-label="Ukladám…" class="sm:w-auto sm:self-start">Uložiť zobrazenie</x-submit-button>
                </form>
            </x-settings-section>

            <x-settings-section id="heslo" title="Heslo" description="Po zmene ťa odhlásime na všetkých ostatných zariadeniach.">
                <form action="{{ route('settings.password.update') }}" method="POST" class="flex flex-col gap-5">
                    @csrf
                    @method('PUT')

                    <x-form-input type="password" name="current_password" autocomplete="current-password" label="Aktuálne heslo" icon="fa-lock" required />
                    <x-form-input type="password" name="new_password" autocomplete="new-password" label="Nové heslo" icon="fa-key" hint="Aspoň 8 znakov." required />
                    <x-form-input type="password" name="new_password_confirmation" autocomplete="new-password" label="Nové heslo znova" icon="fa-check-double" required />

                    <x-submit-button busy-label="Ukladám…" class="sm:w-auto sm:self-start">Zmeniť heslo</x-submit-button>
                </form>
            </x-settings-section>
        </div>
    </div>
</x-layout>
