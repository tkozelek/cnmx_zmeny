<x-layout title="ABSENCIE">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-5 space-y-8">

        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-neutral-100">Správa absencií</h1>
                <p class="text-sm text-neutral-400">Prehľad a evidencia absencií a dovoleniek.</p>
            </div>
            <button data-modal-target="add-absence-modal" data-modal-toggle="add-absence-modal"
                    class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-lg text-neutral-900 bg-neutral-100 hover:bg-white transition focus:outline-none shadow-md"
                    type="button">
                <i class="fa-solid fa-plus text-xs" aria-hidden="true"></i> Pridať absenciu
            </button>
        </div>

        {{-- Add Absence Modal --}}
        <div id="add-absence-modal" tabindex="-1" aria-hidden="true"
             class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-modal md:h-full">
            <div class="relative p-4 w-full max-w-lg h-full md:h-auto">
                <div class="relative bg-neutral-900 border border-neutral-800 rounded-xl shadow-2xl">
                    <div class="flex items-center justify-between p-5 border-b border-neutral-800 rounded-t-xl">
                        <h3 class="text-lg font-bold text-neutral-100">
                            Pridať absenciu
                        </h3>
                        <button type="button"
                                class="text-neutral-400 bg-transparent hover:bg-neutral-800 hover:text-white rounded-lg text-sm p-1.5 ml-auto inline-flex items-center transition"
                                data-modal-hide="add-absence-modal">
                            <i class="fa-solid fa-xmark text-lg"></i>
                            <span class="sr-only">Zavrieť okno</span>
                        </button>
                    </div>
                    <div class="p-5">
                        <form class="space-y-5" action="{{ route('absences.store') }}" method="post">
                            @csrf
                            @include('partials._datepicker')
                            <div>
                                <label for="reason" class="block mb-2 text-sm font-medium text-neutral-300">Dôvod absencie</label>
                                <input type="text" value="{{ old('reason') }}" name="reason" id="reason"
                                       class="bg-neutral-800 border border-neutral-700 text-neutral-100 placeholder-neutral-500 text-sm rounded-lg focus:border-neutral-500 block w-full p-3 transition focus:outline-none"
                                       placeholder="Napr. dovolenka, lekár, atď.">
                                @error('reason')
                                <p class="text-rose-400 text-xs mt-2">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="submit"
                                    class="w-full text-neutral-900 bg-neutral-100 hover:bg-white font-semibold rounded-lg text-sm px-5 py-3 text-center transition">
                                Odoslať žiadosť
                            </button>
                        </form>
                    </div>
                </div>
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
