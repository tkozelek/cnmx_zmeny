<div @click.away="open = false" class="relative" x-data="{ open: false }">
    <button @click="open = !open" class="flex flex-row items-center w-full px-4 py-2 mt-2 text-sm font-semibold text-left rounded-lg bg-transparent focus:text-white hover:text-white focus:bg-gray-600 hover:bg-gray-600 md:w-auto md:inline md:mt-0 md:ml-4 focus:outline-none focus:shadow-outline">
        <span>{{ auth()->user() }}</span>
        @if(isset($newUserCount) && $newUserCount > 0)
            <span class="inline-flex items-center justify-center ml-1 w-4 h-4 text-xs font-semibold text-red-800 bg-red-200 rounded-full">
                                     {{ $newUserCount }}
                                </span>
        @endif
        <svg fill="currentColor" viewBox="0 0 20 20" :class="{'rotate-180': open, 'rotate-0': !open}" class="inline w-4 h-4 mt-1 ml-1 transition-transform duration-200 transform md:-mt-1"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
    </button>
    <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="z-50 absolute right-0 w-full mt-2 origin-top-right rounded-md shadow-lg md:w-48">
        <div class="px-2 py-2 rounded-md shadow bg-gray-800">
            @if(auth()->user()->hasRole(3))
                <a class="block px-4 py-2 mt-3 text-sm font-semibold bg-transparent rounded-lg hover:bg-gray-600 focus:bg-gray-600 focus:text-white hover:text-white text-gray-200 md:mt-0 focus:outline-none focus:shadow-outline" href="{{route('admin.users.index')}}">
                    Použivatelia
                    @if(isset($newUserCount) && $newUserCount > 0)
                        <span class="inline-flex items-center justify-center w-4 h-4 text-xs font-semibold text-red-800 bg-red-200 rounded-full">
                                                {{ $newUserCount }}
                                            </span>
                    @endif
                </a>
            @endif
            <a class="block px-4 py-2 mt-3 text-sm font-semibold bg-transparent rounded-lg hover:bg-gray-600 focus:bg-gray-600 focus:text-white hover:text-white text-gray-200 md:mt-0 focus:outline-none focus:shadow-outline" href="{{route('holiday.index')}}">
                Absencia
            </a>
            <a class="block px-4 py-2 mt-3 text-sm font-semibold bg-transparent rounded-lg hover:bg-gray-600 focus:bg-gray-600 focus:text-white hover:text-white text-gray-200 md:mt-0 focus:outline-none focus:shadow-outline"
               href="{{route('settings.password')}}">
                Zmena hesla
            </a>
            <a class="block px-4 py-2 mt-3 text-sm font-semibold bg-transparent rounded-lg hover:bg-gray-600 focus:bg-gray-600 focus:text-white hover:text-white text-gray-200 md:mt-0 focus:outline-none focus:shadow-outline" href="{{route('bugreport.index')}}">
                Nahlasiť chybu
            </a>
            <hr>
            <a class="block px-4 py-2 mt-3 text-sm font-semibold bg-transparent rounded-lg hover:bg-gray-600 focus:bg-gray-600 focus:text-white hover:text-white text-gray-200 md:mt-0 focus:outline-none focus:shadow-outline" href="{{url('/logout')}}">
                Odhlasiť
            </a>
        </div>
    </div>
</div>
