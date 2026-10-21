@php
    // Managers are exempt from the deadline (StoreAbsenceRequest::after). For everyone else the
    // picker opens on the first day they may still report, instead of a today that always fails.
    $isManager = auth()->user()->hasPermissionInTeam('absence.manage');
    $deadlineDays = $isManager ? 0 : $currentTeam->absenceDeadlineDays();
    $firstAllowedDay = now()->addDays($deadlineDays)->format('Y-m-d');
    // Nothing reported yet: an empty state instead of a search, filters and headers over zero rows.
    $hasOwnAbsences = auth()->user()->absences()->exists();
@endphp
<x-layout title="Absencie" description="Nahlás a spravuj svoje absencie a dovolenky.">
    <div
        x-data="absenceRangePicker('{{ old('date_from', $firstAllowedDay) }}', '{{ old('date_to', $firstAllowedDay) }}', @js($errors->any() || request()->boolean('nova')), @js($isManager ? null : $firstAllowedDay))"
        x-init="$nextTick(() => initFlatpickr())"
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
                        <h3 id="modal-absence-title" class="text-lg font-bold text-neutral-100">Pridať absenciu</h3>
                    </div>
                    <button @click="openModal = false" type="button" aria-label="Zatvoriť okno" class="text-neutral-400 hover:text-white rounded-lg p-1.5 transition">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                {{-- Form --}}
                <form action="{{ route('absences.store') }}" method="POST" class="p-6 space-y-5" @submit="sending = true">
                    @csrf

                    {{-- Error Alert --}}
                    @if($errors->any())
                        <div id="absence-errors" role="alert" class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-sm space-y-1">
                            <p class="font-bold flex items-center gap-2">
                                <i class="fa-solid fa-circle-exclamation text-rose-400" aria-hidden="true"></i>
                                Chyba pri ukladaní absencie:
                            </p>
                            <ul class="list-disc list-inside text-sm space-y-0.5 pl-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Flatpickr Range Select --}}
                    <div>
                        <label for="absence-range" class="block mb-2 text-sm font-semibold text-neutral-200">
                            Rozsah dátumov absencie <span class="text-rose-400" aria-hidden="true">*</span>
                        </label>
                        <div class="relative">
                            <input x-ref="rangeInput" type="text" placeholder="Vyber rozsah dátumov…" aria-label="Rozsah dátumov absencie" class="w-full rounded-lg border border-neutral-500 bg-neutral-800 px-3.5 py-2.5 text-base sm:text-sm text-neutral-100 placeholder-neutral-400 focus:border-neutral-500 focus:outline-none cursor-pointer">
                            <input type="hidden" name="date_from" :value="dateFrom">
                            <input type="hidden" name="date_to" :value="dateTo">
                        </div>
                        <p id="absence-range-hint" class="text-xs text-neutral-400 mt-1">
                            Vyber prvý a posledný deň (pri jednom dni ten istý deň dvakrát).
                            @if($deadlineDays > 0)
                                Absenciu treba nahlásiť aspoň {{ $deadlineDays }} {{ $deadlineDays === 1 ? 'deň' : ($deadlineDays <= 4 ? 'dni' : 'dní') }} vopred.
                            @endif
                        </p>
                    </div>

                    {{-- Required Reason --}}
                    <div>
                        <label for="reason" class="block mb-2 text-sm font-semibold text-neutral-200">
                            Dôvod absencie <span class="text-rose-400" aria-hidden="true">*</span>
                        </label>
                        <input
                            type="text"
                            id="reason"
                            name="reason"
                            value="{{ old('reason') }}"
                            required
                            maxlength="500"
                            aria-describedby="reason-hint @error('reason') absence-errors @enderror"
                            @error('reason') aria-invalid="true" @enderror
                            placeholder="Napr. dovolenka, skúškové…"
                            class="w-full rounded-lg border border-neutral-500 bg-neutral-800 px-3.5 py-2.5 text-base sm:text-sm text-neutral-100 placeholder-neutral-400 focus:border-neutral-500 focus:outline-none"
                        >
                        <p id="reason-hint" class="text-xs text-neutral-400 mt-1">Neuvádzaj zdravotné dôvody ani diagnózy – pri chorobe stačí „osobné dôvody“. Maximálne 500 znakov.</p>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-neutral-800">
                        <button @click="openModal = false" type="button" class="px-4 py-2.5 text-sm font-semibold text-neutral-300 hover:text-white rounded-lg hover:bg-neutral-800 transition">
                            Zrušiť
                        </button>
                        <button type="submit" :disabled="sending" class="px-5 py-2.5 text-sm font-semibold text-neutral-900 bg-neutral-100 hover:bg-white rounded-lg transition disabled:opacity-60">
                            Nahlásiť absenciu
                        </button>
                    </div>
                </form>
            </div>
        </div>
        </template>


        {{-- 1. Table visible strictly to MANAGERS and ADMINS: Všetky absencie zamestnancov --}}
        @can('viewAny', App\Models\Absence::class)
            <div class="space-y-3">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-users text-brand-400 text-base"></i>
                    <h2 class="text-lg font-bold text-neutral-100">Všetky absencie zamestnancov</h2>
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
                    @can('create', App\Models\Absence::class)
                        <x-slot:action>
                            <button @click="openModal = true" type="button" aria-haspopup="dialog"
                                    class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-neutral-600 px-4 text-sm font-semibold text-neutral-100 transition hover:border-neutral-400 hover:bg-neutral-800">
                                <i class="fa-solid fa-plus text-xs" aria-hidden="true"></i> Pridať absenciu
                            </button>
                        </x-slot:action>
                    @endcan
                </x-empty-state>
            @endif
        </div>



    </div>
</x-layout>
