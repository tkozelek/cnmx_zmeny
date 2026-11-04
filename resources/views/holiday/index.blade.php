<x-layout title="Absencie" description="Nahlás a spravuj svoje absencie.">
    @push('head')
        @rappasoftTableStyles
        @rappasoftTableThirdPartyStyles
        @rappasoftTableScripts
        @rappasoftTableThirdPartyScripts
    @endpush

    <div
        x-data="absenceRangePicker(@js(old('date_from', $firstAllowedDay)), @js(old('date_to', $firstAllowedDay)), @js($errors->any() || $openDialog), @js($minDate), @js($repeat))"
        x-init="$nextTick(() => initFlatpickr())"
        {{-- Back from the next page restores this one from bfcache with the button still disabled. --}}
        @pageshow.window="sending = false"
        class="container mx-auto px-4 sm:px-6 lg:px-8 py-5 space-y-8"
    >

        <x-page-header icon="fa-calendar-days" title="Absencie" subtitle="Dni, kedy nemôžeš pracovať.">
            @can('create', App\Models\Absence::class)
            <button @click="openModal = true"
                    aria-haspopup="dialog"
                    :aria-expanded="openModal.toString()"
                    class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-lg text-neutral-900 bg-neutral-100 hover:bg-white transition focus:outline-none shadow-md"
                    type="button">
                <i class="fa-solid fa-plus text-xs" aria-hidden="true"></i> Pridať absenciu
            </button>
            @endcan
        </x-page-header>

        {{-- Add Absence Alpine Modal Component with x-teleport to body --}}
        <template x-teleport="body">
            <div
                x-cloak
                x-show="openModal"
                @keydown.escape.window="openModal = false"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80 backdrop-blur-md overflow-y-auto"
                {{-- .self, not .away on the dialog: the date picker lives outside the dialog in the DOM, so
                     changing its month counted as an outside click and closed the whole form. --}}
                @click.self="openModal = false"
            >
                <div x-trap="openModal"
                     role="dialog"
                     aria-modal="true"
                     aria-labelledby="modal-absence-title"
                     class="relative w-full max-w-lg rounded-lg border border-neutral-800 bg-neutral-900 shadow-xl overflow-hidden my-auto">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-800 bg-neutral-950">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-calendar-plus text-brand-400 text-lg"></i>
                        <h2 id="modal-absence-title" class="text-lg font-bold text-neutral-100">Pridať absenciu</h2>
                    </div>
                    <button @click="openModal = false" type="button" aria-label="Zatvoriť okno" class="text-neutral-400 hover:text-white rounded-lg p-1.5 transition">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                {{-- Form --}}
                <form action="{{ route('absences.store') }}" method="POST" class="p-6 space-y-5" @submit="sending = true">
                    @csrf

                    <x-error-summary id="absence-errors" title="Absenciu sa nepodarilo uložiť:" :fields="$errorFields" />

                    <input type="hidden" name="date_from" :value="dateFrom">
                    <input type="hidden" name="date_to" :value="dateTo">

                    <fieldset>
                        <legend class="sr-only">Typ absencie</legend>
                        <div class="grid grid-cols-2 gap-1 rounded-lg border border-neutral-800 bg-neutral-950 p-1">
                            <label>
                                <input type="radio" name="recurring" value="0" x-model.boolean="recurring" class="peer sr-only">
                                <span class="flex h-11 cursor-pointer items-center justify-center gap-2 rounded-md text-sm font-semibold text-neutral-400 transition hover:text-neutral-200 peer-checked:bg-neutral-800 peer-checked:text-white peer-checked:shadow peer-focus-visible:ring-2 peer-focus-visible:ring-brand-400">
                                    <i class="fa-solid fa-calendar-days text-xs" aria-hidden="true"></i> Jednorazová
                                </span>
                            </label>
                            <label>
                                <input type="radio" name="recurring" value="1" x-model.boolean="recurring" class="peer sr-only">
                                <span class="flex h-11 cursor-pointer items-center justify-center gap-2 rounded-md text-sm font-semibold text-neutral-400 transition hover:text-neutral-200 peer-checked:bg-neutral-800 peer-checked:text-white peer-checked:shadow peer-focus-visible:ring-2 peer-focus-visible:ring-brand-400">
                                    <i class="fa-solid fa-repeat text-xs" aria-hidden="true"></i> Každý týždeň
                                </span>
                            </label>
                        </div>
                    </fieldset>

                    <div x-show="recurring" x-cloak>
                        <fieldset>
                            <legend class="mb-2 text-sm font-semibold text-neutral-200">
                                Ktorý deň <span class="text-rose-400" aria-hidden="true">*</span>
                            </legend>
                            <div class="grid grid-cols-7 gap-1.5">
                                @foreach($weekdays as $value => $weekday)
                                    <label>
                                        <input type="radio"
                                               id="absence-weekday-{{ $value }}"
                                               name="day_of_week"
                                               value="{{ $value }}"
                                               x-model.number="dayOfWeek"
                                               class="peer sr-only"
                                               :disabled="!recurring"
                                               :required="recurring">
                                        <span class="flex h-11 cursor-pointer items-center justify-center rounded-lg border border-neutral-700 bg-neutral-800 text-sm font-semibold text-neutral-300 transition hover:border-neutral-500 peer-checked:border-brand-400 peer-checked:bg-brand-500/15 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-brand-400">
                                            <span aria-hidden="true">{{ $weekday['short'] }}</span>
                                            <span class="sr-only">{{ $weekday['name'] }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    </div>

                    {{-- Flatpickr Range Select: the days of a one-off absence, or the period of a weekly one --}}
                    <div>
                        <label for="absence-range" class="block mb-2 text-sm font-semibold text-neutral-200">
                            <span x-text="recurring ? 'Obdobie' : 'Dni absencie'">Dni absencie</span> <span class="text-rose-400" aria-hidden="true">*</span>
                        </label>
                        <input x-ref="rangeInput" type="text" placeholder="Vyber rozsah dátumov…" aria-label="Dni absencie" class="w-full rounded-lg border border-neutral-500 bg-neutral-800 px-3.5 py-2.5 text-base sm:text-sm text-neutral-100 placeholder-neutral-400 focus:border-neutral-500 focus:outline-none cursor-pointer">
                        <p id="absence-range-hint" class="text-xs text-neutral-400 mt-1">
                            <span x-show="!recurring">Klikni na prvý a&nbsp;posledný deň. Jeden deň = klikni naň dvakrát.</span>
                            <span x-show="recurring" x-cloak>Klikni na prvý a&nbsp;posledný deň obdobia.</span>
                            @if($deadlineLabel)
                                Nahlás aspoň {{ $deadlineLabel }} vopred.
                            @endif
                        </p>
                    </div>

                    <p x-show="recurring" x-cloak class="flex items-start gap-2 rounded-lg border border-brand-500/30 bg-brand-500/10 px-3.5 py-2.5 text-sm text-brand-200" aria-live="polite">
                        <i class="fa-solid fa-repeat mt-0.5 text-xs" aria-hidden="true"></i>
                        <span x-text="recurrenceSummary"></span>
                    </p>

                    {{-- Required Reason --}}
                    <div>
                        <label for="reason" class="block mb-2 text-sm font-semibold text-neutral-200">
                            Dôvod <span class="text-rose-400" aria-hidden="true">*</span>
                        </label>
                        <input
                            type="text"
                            id="reason"
                            name="reason"
                            value="{{ old('reason') }}"
                            required
                            maxlength="500"
                            @error('reason') aria-invalid="true" aria-describedby="absence-errors" @enderror
                            :placeholder="recurring ? 'Napr. škola' : 'Napr. dovolenka, skúškové…'"
                            class="w-full rounded-lg border border-neutral-500 bg-neutral-800 px-3.5 py-2.5 text-base sm:text-sm text-neutral-100 placeholder-neutral-400 focus:border-neutral-500 focus:outline-none"
                        >
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-neutral-800">
                        <button @click="openModal = false" type="button" class="px-4 py-2.5 text-sm font-semibold text-neutral-300 hover:text-white rounded-lg hover:bg-neutral-800 transition">
                            Zrušiť
                        </button>
                        {{-- Until the calendar has loaded, the hidden dates are only the defaults. --}}
                        <button type="submit" :disabled="sending || ! picker" class="px-5 py-2.5 text-sm font-semibold text-neutral-900 bg-neutral-100 hover:bg-white rounded-lg transition disabled:opacity-60">
                            Nahlásiť absenciu
                        </button>
                    </div>
                </form>
            </div>
        </div>
        </template>


        {{-- 1. Table visible strictly to MANAGERS and ADMINS: Všetky absencie brigádnikov --}}
        @can('viewAny', App\Models\Absence::class)
            <div class="space-y-3">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-users text-brand-400 text-base"></i>
                    <h2 class="text-lg font-bold text-neutral-100">Všetky absencie brigádnikov</h2>
                </div>
                <livewire:absences-data-table />
            </div>
        @endcan

        {{-- 2. Table visible to EVERYONE: Moje absencie --}}
        <div class="space-y-3 @can('viewAny', App\Models\Absence::class) pt-6 border-t border-neutral-800 @endcan">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-user-clock text-brand-400 text-base"></i>
                <h2 class="text-lg font-bold text-neutral-100">Moje absencie</h2>
            </div>
            @if($hasOwnAbsences)
                <livewire:my-absences-data-table />
            @else
                <x-empty-state icon="fa-umbrella-beach" message="Zatiaľ nemáš nahlásenú žiadnu absenciu.">
                    Keď nebudeš môcť prísť, nahlás to vopred – manažér to uvidí pri týždni.
                </x-empty-state>
            @endif
        </div>
    </div>
</x-layout>
