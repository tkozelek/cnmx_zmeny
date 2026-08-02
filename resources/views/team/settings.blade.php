<x-layout>
    <div class="container mx-auto px-4 py-8 max-w-3xl">
        <div class="flex flex-col gap-6">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-neutral-800 pb-5">
                <div>
                    <h1 class="text-2xl font-bold text-white flex items-center gap-2.5">
                        <i class="fa-solid fa-sliders text-sky-400"></i>
                        Správa kina - {{ $team->name }}
                    </h1>

                    <p class="text-xs text-neutral-400 mt-1">Konfigurácia parametrov týždňa, absencií a nastavení prevádzky</p>
                </div>
            </div>

            <!-- Form Card -->
            <div class="bg-neutral-900/90 rounded-2xl border border-neutral-800 shadow-xl p-6 sm:p-8">
                @if(session('message'))
                    <div class="mb-6 rounded-xl bg-emerald-500/10 border border-emerald-500/30 p-4 text-xs font-semibold text-emerald-400 flex items-center gap-2">
                        <i class="fa-solid fa-circle-check text-sm"></i>
                        {{ session('message') }}
                    </div>
                @endif

                @if(! $canEdit)
                    <div class="mb-6 rounded-xl bg-amber-500/10 border border-amber-500/30 p-4 text-xs font-semibold text-amber-400 flex items-center gap-2">
                        <i class="fa-solid fa-lock text-sm"></i>
                        Máte iba práva na zobrazenie nastavení. Polia sú vo forme iba na čítanie.
                    </div>
                @endif

                <form action="{{ route('team.settings.update') }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <!-- Cinema Name -->
                    <x-form-input
                        type="text"
                        name="name"
                        label="Názov kina / prevádzky"
                        placeholder="Kino Žilina"
                        :value="old('name', $team->name)"
                        icon="fa-building-user"
                        :disabled="!$canEdit"
                        required
                    />

                    <div class="my-6 border-t border-neutral-800"></div>

                    <div class="space-y-4">
                        <h2 class="text-sm font-semibold uppercase tracking-wider text-sky-400 flex items-center gap-2">
                            <i class="fa-solid fa-calendar-week text-xs"></i>
                            Plánovanie & Týždne
                        </h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <!-- Week Start Day -->
                            <div>
                                <label for="week_start_day" class="block mb-2 text-xs font-semibold uppercase tracking-wider text-neutral-400">Začiatok týždňa</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-neutral-500">
                                        <i class="fa-solid fa-play"></i>
                                    </div>
                                    <select
                                        name="week_start_day"
                                        id="week_start_day"
                                        @disabled(! $canEdit)
                                        required
                                        class="w-full px-4 py-3 pl-11 rounded-xl bg-neutral-900 border border-neutral-800 text-white placeholder-neutral-500 transition-all duration-200 text-sm shadow-inner focus:outline-none focus:ring-2 focus:ring-sky-500/60 focus:border-sky-500 hover:border-neutral-700 appearance-none cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed"
                                    >
                                        @foreach($weekDays as $dayValue => $dayName)
                                            <option value="{{ $dayValue }}" @selected(old('week_start_day', $settings->week_start_day) == $dayValue)>
                                                {{ $dayName }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-neutral-500">
                                        <i class="fa-solid fa-chevron-down text-xs"></i>
                                    </div>
                                </div>
                                <p class="text-[11px] text-neutral-500 mt-1">Deň, ktorým sa začína nový pracovný týždeň.</p>
                                @error('week_start_day')
                                    <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Lookahead Weeks -->
                            <div>
                                <x-form-input
                                    type="number"
                                    name="week_lookahead"
                                    label="Počet týždňov dopredu"
                                    placeholder="5"
                                    :value="old('week_lookahead', $settings->week_lookahead)"
                                    icon="fa-forward-step"
                                    min="1"
                                    max="52"
                                    :disabled="!$canEdit"
                                    required
                                />
                                <p class="text-[11px] text-neutral-500 mt-1">Koľko týždňov do budúcna môžu zamestnanci vidieť a zapisovať sa na zmeny.</p>
                            </div>
                        </div>
                    </div>


                    <div class="my-6 border-t border-neutral-800"></div>

                    <div class="space-y-4">
                        <h2 class="text-sm font-semibold uppercase tracking-wider text-sky-400 flex items-center gap-2">
                            <i class="fa-solid fa-user-clock text-xs"></i>
                            Absencie & Uzávierky
                        </h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <!-- Absence Deadline Days -->
                            <x-form-input
                                type="number"
                                name="absence_deadline_days"
                                label="Uzávierka absencií (dni dopredu)"
                                placeholder="2"
                                :value="old('absence_deadline_days', $settings->absence_deadline_days)"
                                icon="fa-hourglass-half"
                                min="0"
                                max="30"
                                :disabled="!$canEdit"
                                required
                            />

                            <!-- Stale Absence Deletion Window Days -->
                            <x-form-input
                                type="number"
                                name="stale_absence_deletion_days"
                                label="Uchovanie neaktívnej absencie pred vymazaním (dní, 0 = bez čakania)"
                                placeholder="30"
                                :value="old('stale_absence_deletion_days', $team->staleAbsenceDeletionDays())"
                                icon="fa-trash-can"
                                min="0"
                                max="365"
                                :disabled="!$canEdit"
                                required
                            />

                        </div>


                    </div>

                    <div class="my-6 border-t border-neutral-800"></div>

                    <div class="space-y-4">
                        <h2 class="text-sm font-semibold uppercase tracking-wider text-sky-400 flex items-center gap-2">
                            <i class="fa-solid fa-scale-balanced text-xs"></i>
                            Spravodlivosť rozpisu
                        </h2>

                        <p class="text-[11px] text-neutral-500">
                            Koľko „váži“ odpracovaný deň. Vyššia váha = deň, na ktorý sa málokto hlási dobrovoľne
                            (typicky piatok a víkend) - kto ho odpracuje, má to započítané viac.
                            Ako neobľúbený sa v rozpise označí deň s váhou <strong>nad priemerom týždňa</strong> -
                            len v takom dni sa zoznam nezaradených zoradí podľa toho, kto je na ťahu, a pri menách
                            sa zobrazí poradie spravodlivosti (vyššie číslo = na rade skôr). Ak sú všetky váhy
                            rovnaké, neoznačí sa žiadny deň.
                        </p>

                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-7">
                            @foreach($weekDays as $dayIndex => $dayName)
                                <div>
                                    <label for="fairness_day_weights_{{ $dayIndex }}" class="mb-2 block text-[11px] font-semibold uppercase tracking-wider text-neutral-400">
                                        {{ Str::substr($dayName, 0, 3) }}.
                                    </label>
                                    <input
                                        type="number"
                                        step="0.1"
                                        min="0.1"
                                        max="10"
                                        name="fairness_day_weights[{{ $dayIndex }}]"
                                        id="fairness_day_weights_{{ $dayIndex }}"
                                        value="{{ old('fairness_day_weights.'.$dayIndex, $team->fairnessDayWeights()[$dayIndex]) }}"
                                        @disabled(! $canEdit)
                                        required
                                        title="{{ $dayName }}"
                                        class="w-full rounded-xl border border-neutral-800 bg-neutral-900 px-3 py-3 text-sm text-white shadow-inner transition hover:border-neutral-700 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500/60 disabled:cursor-not-allowed disabled:opacity-60"
                                    >
                                    @error('fairness_day_weights.'.$dayIndex)
                                        <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endforeach
                        </div>

                        @error('fairness_day_weights')
                            <p class="text-xs text-rose-400">{{ $message }}</p>
                        @enderror

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div>
                                <x-form-input
                                    type="number"
                                    name="fairness_window_weeks"
                                    label="Obdobie hodnotenia (týždne dozadu)"
                                    placeholder="12"
                                    :value="old('fairness_window_weeks', $team->fairnessWindowWeeks())"
                                    icon="fa-clock-rotate-left"
                                    min="1"
                                    max="52"
                                    :disabled="!$canEdit"
                                    required
                                />
                                <p class="text-[11px] text-neutral-500 mt-1">Ako ďaleko do minulosti sa počítajú odpracované dni.</p>
                            </div>
                        </div>
                    </div>

                    @if($canEdit)
                        <div class="pt-4 flex justify-end">
                            <button type="submit" class="py-3 px-6 bg-neutral-800 hover:bg-neutral-750 active:bg-neutral-850 text-white font-bold rounded-xl text-sm tracking-widest uppercase border border-neutral-700 hover:border-sky-500/50 shadow-lg transition duration-200 flex items-center gap-2 group">
                                <span>Uložiť nastavenia</span>
                                <i class="fa-solid fa-floppy-disk text-xs text-sky-400"></i>
                            </button>
                        </div>
                    @endif
                </form>
            </div>

        </div>
    </div>
</x-layout>
