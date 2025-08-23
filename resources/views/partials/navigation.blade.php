<div class="w-full text-gray-200 bg-slate-900">
    <div x-data="{ open: false }" class="flex flex-col container px-4 mx-auto md:items-center md:justify-between md:flex-row md:px-6 lg:px-8">
        <div class="p-2 flex flex-row items-center justify-between space-x-2 my-1">
            <a href="{{ auth()->check() ? route('calendar.index') : route('welcome.index') }}" class="text-xl font-semibold tracking-wider uppercase rounded-lg text-white focus:outline-none focus:shadow-outline">
                CINEMAX
            </a>
            <button class="md:hidden rounded-lg focus:outline-none focus:shadow-outline" @click="open = !open">
                <svg fill="currentColor" viewBox="0 0 20 20" class="w-6 h-6">
                    <path x-show="!open" fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM9 15a1 1 0 011-1h6a1 1 0 110 2h-6a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                    <path x-show="open" fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
        <nav :class="{'flex': open, 'hidden': !open}" class="flex-col flex-grow pb-4 md:pb-0 hidden md:flex md:justify-end md:flex-row">
            <a class="px-4 py-2 mt-2 text-sm font-semibold rounded-lg focus:text-black hover:text-white text-gray-200 md:mt-0 hover:bg-gray-600 border-gray-200 md:border-none focus:bg-gray-200 focus:outline-none focus:shadow-outline" href="{{route('help')}}">
                <i class="fa-solid w-6 fa-circle-question"></i>Pomoc
            </a>
            <hr>
            @auth()
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
            @else
                <a class="px-4 py-2 mt-2 text-sm font-semibold rounded-lg focus:text-black hover:text-white text-gray-200 md:mt-0 hover:bg-gray-600 border-gray-200 md:border-none border focus:bg-gray-200 focus:outline-none focus:shadow-outline" href="{{route('login')}}">
                    <i class="fa-solid w-6 fa-user"></i>Prihlasenie
                </a>
                <a class="px-4 py-2 mt-2 text-sm font-semibold rounded-lg focus:text-black hover:text-white text-gray-200 md:mt-0 hover:bg-gray-600 border-gray-200 md:border-none md:border focus:bg-gray-200 focus:outline-none focus:shadow-outline" href="{{route('register')}}">
                    <i class="fa-solid w-6 fa-user-plus"></i>Registracia
                </a>
            @endauth
        </nav>
    </div>
</div>
