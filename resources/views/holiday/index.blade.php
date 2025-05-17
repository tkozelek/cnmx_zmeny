<x-layout>
    <div class="container mx-auto">
        <div class="flex justify-end mt-2 mb-4">
            <button data-modal-target="default-modal" data-modal-toggle="default-modal" class="block text-white focus:ring-4 focus:outline-none font-medium rounded-lg text-sm px-5 py-2.5 text-center bg-blue-600 hover:bg-blue-700 focus:ring-blue-800" type="button">
                <i class="fa-solid fa-plus"></i> Pridať absenciu
            </button>
        </div>

        <div>
            <div id="default-modal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
                <div class="relative p-4 w-full max-w-md max-h-full">
                    <!-- Modal content -->
                    <div class="relative rounded-lg shadow bg-gray-700">
                        <!-- Modal header -->
                        <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t border-gray-600">
                            <h3 class="text-xl font-semibold text-white">
                                Pridať absenciu
                            </h3>
                            <button type="button" class="end-2.5 text-gray-400 bg-transparent rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center hover:bg-gray-600 hover:text-white" data-modal-hide="default-modal">
                                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                                </svg>
                                <span class="sr-only">Zavrieť okno</span>
                            </button>
                        </div>
                        <!-- Modal body -->
                        <div class="px-4 pb-4">
                            <form class="space-y-4" action="{{url('/dovolenka/save')}}" method="post">
								<input type="hidden" name="form_token" value="{{ session()->get('form_token') }}">
                                @csrf
                                @include('partials._datepicker')
                                <div>
                                    <label for="popis" class="ml-1 block mb-2 text-sm font-medium text-white"></label>
                                    <input type="text" value="{{old('popis')}}" name="popis" id="popis" class="border sm:text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 bg-gray-700 border-gray-600 placeholder-gray-400 text-white focus:ring-blue-500 focus:border-blue-500" placeholder="Dôvod" >
                                    @error('popis')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <button type="submit" class="w-full text-white focus:ring-4 focus:outline-none font-medium rounded-lg text-sm px-5 py-2.5 text-center bg-blue-600 hover:bg-blue-700 focus:ring-blue-800 uppercase">odoslať</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if(isset($absences) && count($absences) != 0)
        <div class="text-white font-bold tracking-wider text-xl uppercase"><span>Tvoje absencie</span></div>
            <div class="my-2 overflow-x-auto">
                <table class="table-auto w-full text-sm text-left rtl:text-right text-gray-300 text-gray-400 table-auto">
                    <thead class="text-xs uppercase bg-gray-600 text-gray-200">
                        <tr class="">
                            <x-table-cell-header>Začiatok</x-table-cell-header>
                            <x-table-cell-header>Koniec</x-table-cell-header>
                            <x-table-cell-header>Dôvod</x-table-cell-header>
                            <x-table-cell-header>Akcie</x-table-cell-header>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($absences as $absence)
                            <x-table-row>
                                <x-table-cell>{{ App\Helpers::getDateFromAttribute($absence->date_from, 'd.m.Y') }}</x-table-cell>
                                <x-table-cell>{{ App\Helpers::getDateFromAttribute($absence->date_to, 'd.m.Y') }}</x-table-cell>
                                <x-table-cell> {{ $absence->popis }}</x-table-cell>
                                @if(!$absence->date_canceled && $absence->date_to >= \Carbon\Carbon::now()->format('Y-m-d'))
                                    <x-table-cell class="flex flex-row flex-wrap items-center !px-6 !py-2">
                                        <form action="{{ route('holiday.end', $absence->id) }}" method="POST" style="display: inline;">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="py-2 px-3 bg-red-400 hover:bg-red-600 text-black font-bold rounded" style="margin: 0;">
                                                <i class="fa-solid fa-x"></i>
                                            </button>
                                        </form>
                                    </x-table-cell>
                                @elseif(now() >= $absence->date_to->addWeek())
                                    <x-table-cell class="!py-2 !px-6">
                                        <form action="{{ route('holiday.destroy', $absence->id) }}" method="POST" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="py-2 px-3 bg-red-400 hover:bg-red-600 text-black font-bold rounded" style="margin: 0;">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </x-table-cell>
                                @else
                                    <x-table-cell>
                                        neaktivne
                                    </x-table-cell>
                                @endif
                            </x-table-row>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if(isset($active) && count($active) != 0)
            <div class="my-2 overflow-x-auto">
                <div class="text-white font-bold tracking-wider text-xl uppercase float-start before:text-green-600 my-2 mt-4">
                    <span class="text-green-600">Aktívne</span> absencie všetkých zamestnancov
                </div>
                <table class="table-auto w-full text-sm text-left rtl:text-right text-gray-300">
                    <thead class="text-xs uppercase bg-gray-600 text-gray-200">
                        <tr class="">
                            <x-table-cell-header>Meno</x-table-cell-header>
                            <x-table-cell-header>Začiatok</x-table-cell-header>
                            <x-table-cell-header>Koniec</x-table-cell-header>
                            <x-table-cell-header>Vytvorené</x-table-cell-header>
                            <x-table-cell-header>Dôvod</x-table-cell-header>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($active as $absence)
                        <x-table-row>
                            <x-table-cell>{{ $absence->user }}</x-table-cell>
                            <x-table-cell>{{ App\Helpers::getDateFromAttribute($absence->date_from, 'd.m.Y') }}</x-table-cell>
                            <x-table-cell>{{ App\Helpers::getDateFromAttribute($absence->date_to, 'd.m.Y') }}</x-table-cell>
                            <x-table-cell>{{ App\Helpers::getDateFromAttribute($absence->created_at, 'd.m.Y H:i:s') }}</x-table-cell>
                            <x-table-cell> {{ $absence->popis }}</x-table-cell>
                        </x-table-row>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if(isset($inactive) && count($inactive) != 0)
            <div class="my-2 overflow-x-auto">
                <div class="text-white font-bold tracking-wider text-xl uppercase my-2 mt-4">
                    <span class="text-red-600">Vypršané</span> absencie všetkých zamestnancov
                </div>
                <table class="table-auto w-full text-sm text-left rtl:text-right text-gray-300">
                    <thead class="text-xs uppercase bg-gray-600 text-gray-200">
                        <tr class="">
                            <x-table-cell-header>Meno</x-table-cell-header>
                            <x-table-cell-header>Začiatok</x-table-cell-header>
                            <x-table-cell-header>Koniec</x-table-cell-header>
                            <x-table-cell-header>Vytvorené</x-table-cell-header>
                            <x-table-cell-header>Dôvod</x-table-cell-header>
							<x-table-cell-header>Akcie</x-table-cell-header>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($inactive as $absence)
                        <x-table-row>
                            <x-table-cell>{{ $absence->user }}</x-table-cell>
                            <x-table-cell>{{ App\Helpers::getDateFromAttribute($absence->date_from, 'd.m.Y') }}</x-table-cell>
                            <x-table-cell>{{ App\Helpers::getDateFromAttribute($absence->date_to, 'd.m.Y') }}</x-table-cell>
                            <x-table-cell>{{ App\Helpers::getDateFromAttribute($absence->created_at, 'd.m.Y H:i:s') }}</x-table-cell>
                            <x-table-cell> {{ $absence->popis }}</x-table-cell>
							<x-table-cell class="!py-2 !px-6">
                                        <form action="{{ route('holiday.destroy', $absence->id) }}" method="POST" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="py-2 px-3 bg-red-400 hover:bg-red-600 text-black font-bold rounded" style="margin: 0;">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </x-table-cell>
                        </x-table-row>
                    @endforeach
                    </tbody>
                </table>
				{{ $inactive->links() }}
            </div>
        @endif
    </div>
</x-layout>
