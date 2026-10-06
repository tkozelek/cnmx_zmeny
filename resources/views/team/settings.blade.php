<x-layout title="Správa kina" description="Nastavenia týždňa, absencií a prevádzky vášho kina.">
    <div class="container mx-auto px-4 py-8 max-w-3xl">
        <div class="flex flex-col gap-6">

            <x-page-header
                icon="fa-sliders"
                :title="'Správa kina - '.$team->name"
                subtitle="Konfigurácia parametrov týždňa, absencií a nastavení prevádzky"
            />

            <!-- Form Card -->
            <div class="bg-neutral-900 rounded-lg border border-neutral-800 shadow-sm p-6 sm:p-8">
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
                        <h2 class="text-base font-semibold text-neutral-100 flex items-center gap-2">
                            <i class="fa-solid fa-calendar-week text-xs text-neutral-400" aria-hidden="true"></i>
                            Plánovanie a týždne
                        </h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <!-- Week Start Day -->
                            <div>
                                <label for="week_start_day" class="block mb-2 text-sm font-medium text-neutral-300">Začiatok týždňa</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-neutral-400">
                                        <i class="fa-solid fa-play"></i>
                                    </div>
                                    <select
                                        name="week_start_day"
                                        id="week_start_day"
                                        @disabled(! $canEdit)
                                        required
                                        class="w-full px-4 py-3 pl-11 rounded-xl bg-neutral-900 border border-neutral-700 text-white placeholder-neutral-400 transition-all duration-200 text-base sm:text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/60 focus:border-brand-500 hover:border-neutral-700 appearance-none cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed"
                                    >
                                        @foreach($weekDays as $dayValue => $dayName)
                                            <option value="{{ $dayValue }}" @selected(old('week_start_day', $settings->week_start_day) == $dayValue)>
                                                {{ $dayName }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-neutral-400">
                                        <i class="fa-solid fa-chevron-down text-xs"></i>
                                    </div>
                                </div>
                                <p class="text-xs text-neutral-400 mt-1">Deň, ktorým sa začína nový pracovný týždeň.</p>
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
                                <p class="text-xs text-neutral-400 mt-1">Koľko týždňov dopredu sa dá v kalendári pozerať a zapisovať.</p>
                            </div>
                        </div>
                    </div>


                    <div class="my-6 border-t border-neutral-800"></div>

                    <div class="space-y-4">
                        <h2 class="text-base font-semibold text-neutral-100 flex items-center gap-2">
                            <i class="fa-solid fa-user-clock text-xs text-neutral-400" aria-hidden="true"></i>
                            Absencie a uzávierky
                        </h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <!-- Absence Deadline Days -->
                            <div>
                                <x-form-input
                                    type="number"
                                    name="absence_deadline_days"
                                    label="Uzávierka absencií (dni)"
                                    placeholder="2"
                                    :value="old('absence_deadline_days', $settings->absence_deadline_days)"
                                    icon="fa-hourglass-half"
                                    min="0"
                                    max="30"
                                    :disabled="!$canEdit"
                                    required
                                />
                                <p class="text-xs text-neutral-400 mt-1">Koľko dní vopred musia brigádnici nahlásiť absenciu. Manažérov sa to netýka.</p>
                            </div>

                            <!-- Stale Absence Deletion Window Days -->
                            <div>
                                <x-form-input
                                    type="number"
                                    name="stale_absence_deletion_days"
                                    label="Vymazanie skončenej absencie (dni)"
                                    placeholder="30"
                                    :value="old('stale_absence_deletion_days', $team->staleAbsenceDeletionDays())"
                                    icon="fa-trash-can"
                                    min="0"
                                    max="365"
                                    :disabled="!$canEdit"
                                    required
                                />
                                <p class="text-xs text-neutral-400 mt-1">Po koľkých dňoch si brigádnik môže vymazať skončenú absenciu. 0 = hneď.</p>
                            </div>

                        </div>


                    </div>

                    @if($canEdit)
                        <div class="pt-4 flex justify-end">
                            <button type="submit" class="py-2.5 px-5 bg-neutral-100 hover:bg-white text-neutral-900 font-semibold rounded-lg text-sm transition flex items-center gap-2">
                                <i class="fa-solid fa-floppy-disk text-xs" aria-hidden="true"></i>
                                <span>Uložiť nastavenia</span>
                            </button>
                        </div>
                    @endif
                </form>
            </div>

        </div>
    </div>
</x-layout>
