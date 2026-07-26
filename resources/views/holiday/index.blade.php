<x-layout title="ABSENCIE">
    <div
        x-data="{
            openModal: @js($errors->any()),
            dateFrom: '{{ old('date_from', now()->format('Y-m-d')) }}',
            dateTo: '{{ old('date_to', now()->format('Y-m-d')) }}',
            initFlatpickr() {
                if (typeof window.flatpickr !== 'function') return;
                window.flatpickr($refs.rangeInput, {
                    mode: 'range',
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altInputClass: 'w-full rounded-lg border border-neutral-700 bg-neutral-800 px-3.5 py-2.5 text-sm text-neutral-100 placeholder-neutral-500 focus:border-neutral-500 focus:outline-none cursor-pointer',
                    altFormat: 'j. n. Y',
                    defaultDate: [this.dateFrom, this.dateTo],
                    onChange: (selectedDates) => {
                        if (selectedDates.length >= 1) {
                            const d1 = selectedDates[0].getFullYear() + '-' + String(selectedDates[0].getMonth() + 1).padStart(2, '0') + '-' + String(selectedDates[0].getDate()).padStart(2, '0');
                            this.dateFrom = d1;
                            if (selectedDates.length === 2) {
                                const d2 = selectedDates[1].getFullYear() + '-' + String(selectedDates[1].getMonth() + 1).padStart(2, '0') + '-' + String(selectedDates[1].getDate()).padStart(2, '0');
                                this.dateTo = d2;
                            } else {
                                this.dateTo = d1;
                            }
                        }
                    }
                });
            }
        }"
        x-init="$nextTick(() => initFlatpickr())"
        class="container mx-auto px-4 sm:px-6 lg:px-8 py-5 space-y-8"
    >

        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-neutral-100">Správa absencií</h1>
                <p class="text-sm text-neutral-400">Prehľad a evidencia absencií a dovoleniek.</p>
            </div>
            <button @click="openModal = true"
                    class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-lg text-neutral-900 bg-neutral-100 hover:bg-white transition focus:outline-none shadow-md"
                    type="button">
                <i class="fa-solid fa-plus text-xs" aria-hidden="true"></i> Pridať absenciu
            </button>
        </div>

        {{-- Add Absence Alpine Modal Component with z-[100] to cover top z-50 sticky navbar --}}
        <div
            x-cloak
            x-show="openModal"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80 backdrop-blur-md overflow-y-auto"
        >
            <div @click.away="openModal = false" class="relative w-full max-w-lg rounded-2xl border border-neutral-800 bg-neutral-900 shadow-2xl overflow-hidden">
                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-800 bg-neutral-950">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-calendar-plus text-sky-400 text-lg"></i>
                        <h3 class="text-lg font-bold text-neutral-100">Pridať absenciu</h3>
                    </div>
                    <button @click="openModal = false" type="button" class="text-neutral-400 hover:text-white rounded-lg p-1.5 transition">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                {{-- Form --}}
                <form action="{{ route('absences.store') }}" method="POST" class="p-6 space-y-5">
                    @csrf

                    {{-- Error Alert --}}
                    @if($errors->any())
                        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-sm space-y-1">
                            <p class="font-bold flex items-center gap-2">
                                <i class="fa-solid fa-circle-exclamation text-rose-400"></i>
                                Chyba pri ukladaní absencie:
                            </p>
                            <ul class="list-disc list-inside text-xs space-y-0.5 pl-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Flatpickr Range Select --}}
                    <div>
                        <label class="block mb-2 text-sm font-semibold text-neutral-200">
                            Rozsah dátumov absencie <span class="text-rose-400">*</span>
                        </label>
                        <div class="relative">
                            <input x-ref="rangeInput" type="text" placeholder="Vyberte rozsah dátumov..." class="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-3.5 py-2.5 text-sm text-neutral-100 placeholder-neutral-500 focus:border-neutral-500 focus:outline-none cursor-pointer">
                            <input type="hidden" name="date_from" :value="dateFrom">
                            <input type="hidden" name="date_to" :value="dateTo">
                        </div>
                        <p class="text-xs text-neutral-400 mt-1">Vyberte 1 alebo viac dní trvania absencie.</p>
                    </div>

                    {{-- Required Reason --}}
                    <div>
                        <label for="reason" class="block mb-2 text-sm font-semibold text-neutral-200">
                            Dôvod absencie <span class="text-rose-400">*</span>
                        </label>
                        <input
                            type="text"
                            id="reason"
                            name="reason"
                            value="{{ old('reason') }}"
                            required
                            placeholder="Napr. dovolenka, PN, lekár, atď."
                            class="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-3.5 py-2.5 text-sm text-neutral-100 placeholder-neutral-500 focus:border-neutral-500 focus:outline-none"
                        >
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-neutral-800">
                        <button @click="openModal = false" type="button" class="px-4 py-2.5 text-sm font-semibold text-neutral-300 hover:text-white rounded-lg hover:bg-neutral-800 transition">
                            Zrušiť
                        </button>
                        <button type="submit" class="px-5 py-2.5 text-sm font-semibold text-neutral-900 bg-neutral-100 hover:bg-white rounded-lg transition shadow-md">
                            Odoslať žiadosť
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- 1. Table visible to EVERYONE: Moje absencie --}}
        <div class="space-y-3">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-user-clock text-sky-400 text-base"></i>
                <h2 class="text-lg font-bold text-neutral-100">Moje absencie</h2>
            </div>
            <livewire:my-absences-data-table />
        </div>

        {{-- 2. Table visible strictly to MANAGERS and ADMINS: Všetky absencie zamestnancov --}}
        @can('viewAny', App\Models\Absence::class)
            <div class="space-y-3 pt-6 border-t border-neutral-800">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-users text-amber-400 text-base"></i>
                        <h2 class="text-lg font-bold text-neutral-100">Všetky absencie zamestnancov</h2>
                    </div>
                    <span class="px-3 py-1 text-xs font-semibold rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/30">Manažérsky prehľad</span>
                </div>
                <livewire:absences-data-table />
            </div>
        @endcan

    </div>
</x-layout>
