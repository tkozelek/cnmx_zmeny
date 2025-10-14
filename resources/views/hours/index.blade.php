<x-layout>
    @isset($users)

    @endisset
    <div class="container mx-auto p-4 sm:p-6 lg:p-8">

        <h1 class="text-3xl sm:text-4xl font-bold mb-3 text-center text-white">Sledovanie dochádzky a mzdy</h1>

        @isset($user)
            <h3 class="text-xl font-bold my-2 text-center text-white">{{ $user }}</h3>
        @endisset
        @isset($users)
            <div class="mb-5 w-full flex justify-center items-center">
                <x-selector
                    name="selectedUser"
                    label="Používatelia"
                    :items="$users"
                    item-text="lastname"
                    :redirect="route('hours.index')"
                />
            </div>

        @endisset
        <input type="hidden" id="same_user" name="same_user" value="{{ !isset($user) || $user->id === auth()->id() }}">

        @include('hours.partials._rate')

        <div class="bg-gray-800 rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between mb-6">
                <button id="prevMonth"
                        class="p-2 rounded-full hover:bg-gray-700 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
                <h2 id="monthAndYear" class="text-2xl font-bold text-center"></h2>
                <button id="nextMonth"
                        class="p-2 rounded-full hover:bg-gray-700 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>

            <div id="calendar-header"
                 class="grid grid-cols-7 gap-2 text-center font-semibold text-gray-400 text-sm mb-2">
            </div>

            <div id="calendar-grid" class="grid grid-cols-7 gap-2">
            </div>
        </div>

        @include('hours.partials._summary')

        <form action="{{ route('hours.store') }}" method="POST" id="saveForm">
            @csrf
            <button id="saveMonthButton" class="hidden fixed z-20 px-4 py-2 rounded-lg font-bold bg-blue-600 bottom-5 right-5 ">Uložiť mesiac</button>
        </form>

        @include('hours.partials._modal')


    <script>
        window.workdata = @json($workdata);
    </script>


    @vite(['resources/js/classes/main.js'])
</x-layout>
