<x-layout>
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-5">

        <div class="flex justify-end mb-2">
            <button data-modal-target="default-modal" data-modal-toggle="default-modal"
                    class="inline-flex items-center px-5 py-2 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-500 hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 focus:ring-indigo-500 transition-colors duration-150"
                    type="button">
                <i class="fa-solid fa-plus mr-2 mt-1.5 h-5 w-5" aria-hidden="true"></i> Pridať absenciu
            </button>
        </div>

        <div id="add-absence-modal" tabindex="-1" aria-hidden="true"
             class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-modal md:h-full">
            <div class="relative p-4 w-full max-w-lg h-full md:h-auto">
                <div class="relative bg-slate-800 rounded-xl shadow-2xl">
                    <div class="flex items-center justify-between p-5 border-b border-slate-700 rounded-t-xl">
                        <h3 class="text-xl font-semibold text-slate-100">
                            Pridať absenciu
                        </h3>
                        <button type="button"
                                class="text-slate-400 bg-transparent hover:bg-slate-600 hover:text-slate-100 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center transition-colors duration-150"
                                data-modal-hide="add-absence-modal">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path fill-rule="evenodd"
                                      d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                      clip-rule="evenodd"></path>
                            </svg>
                            <span class="sr-only">Zavrieť okno</span>
                        </button>
                    </div>
                    <div class="p-5 pt-0.5">
                        <form class="space-y-6" action="{{url('/dovolenka/save')}}" method="post">
                            <input type="hidden" name="form_token" value="{{ session()->get('form_token') }}">
                            @csrf
                            @include('partials._datepicker')
                            <div>
                                <label for="popis" class="block mb-2 text-sm font-medium text-slate-200">Dôvod absencie</label>
                                <input type="text" value="{{old('popis')}}" name="popis" id="popis"
                                       class="bg-slate-700 border border-slate-600 text-slate-100 placeholder-slate-400 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-3 transition-colors duration-150"
                                       placeholder="Napr. dovolenka, lekár, atď.">
                                @error('popis')
                                <p class="text-red-400 text-xs mt-2">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="submit"
                                    class="w-full text-white bg-indigo-500 hover:bg-indigo-600 focus:ring-4 focus:outline-none focus:ring-indigo-500/50 font-medium rounded-lg text-sm px-5 py-3 text-center uppercase tracking-wider transition-colors duration-150">
                                Odoslať žiadosť
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @if(isset($absences) && count($absences) > 0)
            <section class="mb-6">
                <h2 class="text-2xl font-semibold text-slate-100 mb-2 tracking-tight">Tvoje absencie</h2>
                <div class="bg-slate-800 shadow-xl rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-slate-300">
                            <thead class="text-xs text-slate-200 uppercase bg-slate-700/50">
                            <tr>
                                <x-table-cell-header class="">Začiatok</x-table-cell-header>
                                <x-table-cell-header class="">Koniec</x-table-cell-header>
                                <x-table-cell-header class="">Dôvod</x-table-cell-header>
                                <x-table-cell-header class=" text-center">Akcie</x-table-cell-header>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-700">
                            @foreach($absences as $absence)
                                <tr class="hover:bg-slate-700/30 transition-colors duration-150">
                                    <x-table-cell class=" whitespace-nowrap">{{ App\Helpers::getDateFromAttribute($absence->date_from, 'd.m.Y') }}</x-table-cell>
                                    <x-table-cell class=" whitespace-nowrap">{{ App\Helpers::getDateFromAttribute($absence->date_to, 'd.m.Y') }}</x-table-cell>
                                    <x-table-cell class=" max-w-xs truncate" title="{{ $absence->popis }}"> {{ $absence->popis }}</x-table-cell>
                                    <x-table-cell class="!py-2 text-center">
                                        @if(!$absence->date_canceled && $absence->date_to >= \Carbon\Carbon::now()->format('Y-m-d'))
                                            <form action="{{ route('holiday.end', $absence->id) }}" method="POST" class="inline-block">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="p-2 rounded-md text-amber-400 hover:text-amber-300 bg-amber-900 hover:bg-amber-900/40 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-800 focus:ring-amber-500 transition-all duration-150" title="Zrušiť absenciu">
                                                    <i class="fa-solid fa-xmark fa-fw" aria-hidden="true"></i>
                                                    <span class="sr-only">Zrušiť</span>
                                                </button>
                                            </form>
                                        @elseif(now() >= $absence->date_to->addWeek())
                                            <form action="{{ route('holiday.destroy', $absence->id) }}" method="POST" class="inline-block">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-2 rounded-md bg-red-500 text-red-400 hover:text-red-300 hover:bg-red-900/40 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-800 focus:ring-red-500 transition-all duration-150" title="Vymazať absenciu">
                                                    <i class="fa-solid fa-trash fa-fw" aria-hidden="true"></i>
                                                    <span class="sr-only">Vymazať</span>
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs italic text-slate-500">Neaktívne</span>
                                        @endif
                                    </x-table-cell>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        @endif

        @if(isset($active) && count($active) > 0)
            <section class="mb-6">
                <h2 class="text-2xl font-semibold text-slate-100 mb-2 mt-5 tracking-tight">
                    <span class="text-green-500">Aktívne</span> absencie všetkých zamestnancov
                </h2>
                <div class="bg-slate-800 shadow-xl rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-slate-300">
                            <thead class="text-xs text-slate-200 uppercase bg-slate-700/50">
                            <tr>
                                <x-table-cell-header class="">Meno</x-table-cell-header>
                                <x-table-cell-header class="">Začiatok</x-table-cell-header>
                                <x-table-cell-header class="">Koniec</x-table-cell-header>
                                <x-table-cell-header class="">Vytvorené</x-table-cell-header>
                                <x-table-cell-header class="">Dôvod</x-table-cell-header>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-700">
                            @foreach($active as $absence)
                                <tr class="hover:bg-slate-700/30 transition-colors duration-150">
                                    <x-table-cell class=" whitespace-nowrap font-medium text-slate-100">{{ $absence->user }}</x-table-cell>
                                    <x-table-cell class=" whitespace-nowrap">{{ App\Helpers::getDateFromAttribute($absence->date_from, 'd.m.Y') }}</x-table-cell>
                                    <x-table-cell class=" whitespace-nowrap">{{ App\Helpers::getDateFromAttribute($absence->date_to, 'd.m.Y') }}</x-table-cell>
                                    <x-table-cell class=" whitespace-nowrap">{{ App\Helpers::getDateFromAttribute($absence->created_at, 'd.m.Y H:i:s') }}</x-table-cell>
                                    <x-table-cell class=" max-w-xs truncate" title="{{ $absence->popis }}"> {{ $absence->popis }}</x-table-cell>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        @endif

        @if(isset($inactive) && count($inactive) > 0)
            <section class="mb-6">
                <h2 class="text-2xl font-semibold text-slate-100 mb-2 mt-5 tracking-tight">
                    <span class="text-red-500">Vypršané</span> absencie všetkých zamestnancov
                </h2>
                <div class="bg-slate-800 shadow-xl rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-slate-300">
                            <thead class="text-xs text-slate-200 uppercase bg-slate-700/50">
                            <tr>
                                <x-table-cell-header class="">Meno</x-table-cell-header>
                                <x-table-cell-header class="">Začiatok</x-table-cell-header>
                                <x-table-cell-header class="">Koniec</x-table-cell-header>
                                <x-table-cell-header class="">Vytvorené</x-table-cell-header>
                                <x-table-cell-header class="">Dôvod</x-table-cell-header>
                                <x-table-cell-header class=" text-center">Akcie</x-table-cell-header>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-700">
                            @foreach($inactive as $absence)
                                <tr class="hover:bg-slate-700/30 transition-colors duration-150">
                                    <x-table-cell class="whitespace-nowrap font-medium text-slate-100">{{ $absence->user }}</x-table-cell>
                                    <x-table-cell class=" whitespace-nowrap">{{ App\Helpers::getDateFromAttribute($absence->date_from, 'd.m.Y') }}</x-table-cell>
                                    <x-table-cell class=" whitespace-nowrap">{{ App\Helpers::getDateFromAttribute($absence->date_to, 'd.m.Y') }}</x-table-cell>
                                    <x-table-cell class=" whitespace-nowrap">{{ App\Helpers::getDateFromAttribute($absence->created_at, 'd.m.Y H:i:s') }}</x-table-cell>
                                    <x-table-cell class=" max-w-xs truncate" title="{{ $absence->popis }}"> {{ $absence->popis }}</x-table-cell>
                                    <x-table-cell class="!py-3 text-center">
                                        <form action="{{ route('holiday.destroy', $absence->id) }}" method="POST" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2 rounded-md bg-red-500 text-red-200 hover:text-red-300 hover:bg-red-900/40 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-800 focus:ring-red-500 transition-all duration-150" title="Vymazať absenciu">
                                                <i class="fa-solid fa-trash fa-fw" aria-hidden="true"></i>
                                                <span class="sr-only">Vymazať</span>
                                            </button>
                                        </form>
                                    </x-table-cell>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($inactive->hasPages())
                        <div class="p-6 border-t border-slate-700 bg-slate-800">
                            {{ $inactive->links('vendor.pagination.tailwind') }}
                        </div>
                    @endif
                </div>
            </section>
        @endif

        @if((!isset($absences) || count($absences) == 0))
            <div class="text-center py-12 bg-slate-800 rounded-lg shadow-xl">
                <svg class="mx-auto h-12 w-12 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-2 text-lg font-medium text-slate-300">Žiadne absencie</h3>
                <p class="mt-1 text-sm text-slate-400">
                    Momentálne tu nie sú žiadne záznamy o absenciách.
                </p>
                <div class="mt-6">
                    <button data-modal-target="add-absence-modal" data-modal-toggle="add-absence-modal"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-800 focus:ring-indigo-500 transition-colors duration-150"
                            type="button">
                        <i class="fa-solid fa-plus -ml-1 mr-2 h-5 w-5" aria-hidden="true"></i>
                        Pridať prvú absenciu
                    </button>
                </div>
            </div>
        @endif
    </div>
</x-layout>
