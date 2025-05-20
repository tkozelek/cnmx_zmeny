<div class="flex flex-wrap items-center justify-center gap-3 my-5 md:my-6">

    @if(auth()->user()->hasRole(config('constants.roles.admin')))
        @if($week->locked)
            <a href="{{ route('admin.calendar.lock', ['week' => $week->id]) }}"
               class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium rounded-md shadow-sm text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 focus:ring-amber-500 transition-colors duration-150">
                <i class="fa-solid fa-unlock -ml-1 mr-2 h-4 w-4"></i> Odomknúť
            </a>
        @else
            <a href="{{ route('admin.calendar.lock', ['week' => $week->id]) }}"
               class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 focus:ring-red-500 transition-colors duration-150">
                <i class="fa-solid fa-lock -ml-1 mr-2 h-4 w-4"></i> Zamknúť
            </a>
        @endif

        <a href="{{ route('admin.calendar.export', ['week' => $week->id]) }}"
           class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 focus:ring-green-500 transition-colors duration-150">
            <i class="fa-solid fa-download -ml-1 mr-2 h-4 w-4"></i> Excel export
        </a>
    @endif

    <button data-modal-target="filesManagementModal" data-modal-toggle="filesManagementModal"
            class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 focus:ring-indigo-500 transition-colors duration-150" type="button">
        <i class="fa-solid fa-folder-open -ml-1 mr-2 h-4 w-4"></i> Súbory týždňa
    </button>
</div>

<div id="filesManagementModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-[100] justify-center items-center w-full md:inset-0 h-modal md:h-full">
    <div class="relative p-4 w-full max-w-3xl h-full md:h-auto">
        <div class="relative bg-slate-800 rounded-xl shadow-2xl">
            <div class="flex items-center justify-between p-4 sm:p-5 border-b border-slate-700 rounded-t-xl">
                <h3 class="text-lg sm:text-xl font-semibold text-slate-100">
                    Súbory
                </h3>
                <button type="button" class="text-slate-400 bg-transparent hover:bg-slate-600 hover:text-slate-100 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center transition-colors duration-150" data-modal-hide="filesManagementModal">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                    <span class="sr-only">Zavrieť okno</span>
                </button>
            </div>
            <div class="p-4 sm:p-6">
                <p class="text-slate-300">Obsah pre nahrávanie a zoznam súborov tu...</p>
                @include('partials._fileupload')
            </div>
        </div>
    </div>
</div>
