<nav class="hidden w-full flex-grow items-center pb-4 md:container md:pb-0 md:flex md:justify-end md:flex-row">
    @auth
        @if(auth()->user()->hasRole(3))
            <x-nav-link
                class="relative"
                icon='<i class="fa-solid fa-user"></i>'
                route="admin.users.index"
            >
                Používatelia
                @if(isset($newUserCount) && $newUserCount > 0)
                    <span class="absolute top-1 right-1 -mt-1 -mr-1 flex h-4 w-4">
                        <span class="animate-ping absolute inline-flex h-4 w-4 text-xs font-bold bg-red-500 border-2 border-gray-900 rounded-full"></span>
                        <span class="relative inline-flex rounded-full h-4 w-4 bg-red-500 items-center justify-center text-xs text-white">
                            {{ $newUserCount }}
                        </span>
                    </span>
                @endif
            </x-nav-link>
        @endif
        <x-nav-link
            icon='<i class="fa fa-calendar"></i>'
            route="holiday.index"
        >
            Absencie
        </x-nav-link>
    @endauth
    <x-nav-link
        icon='<i class="fa-solid fa-circle-question"></i>'
        route="help"
    >
        Pomoc
    </x-nav-link>
    @auth
        <x-nav-link
            icon='<i class="fa-solid fa-bug"></i>'
            route="bugreport.index"
        >
            Nahlásiť chybu
        </x-nav-link>
    @endauth

    <div class="hidden md:block w-px h-6 bg-gray-600 mx-4"></div>

    @auth
        <div @click.away="open = false" class="relative" x-data="{ open: false }">
            <button @click="open = !open" class="flex flex-row items-center w-full px-4 py-2 mt-2 text-sm font-semibold text-left bg-transparent rounded-lg hover:bg-gray-700 md:w-auto md:inline md:mt-0 md:ml-2 focus:outline-none focus:shadow-outline transition-colors duration-200">
                <span class="font-medium">{{ auth()->user()->name }}</span>
                <svg fill="currentColor" viewBox="0 0 20 20" :class="{'rotate-180': open, 'rotate-0': !open}" class="inline w-5 h-5 ml-1 transition-transform duration-200 transform"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
            </button>
            <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="z-50 absolute right-0 w-full mt-2 origin-top-right rounded-md shadow-lg md:w-48" style="display: none;">
                <div class="px-2 py-2 rounded-md shadow bg-gray-800">
                    <a class="block px-4 py-2 text-sm font-semibold bg-transparent rounded-lg hover:bg-gray-700 text-gray-200" href="{{route('settings.password')}}">
                        Zmena hesla
                    </a>
                    <div class="border-t border-gray-600 my-1"></div>
                    <a class="block w-full text-left px-4 py-2 text-sm font-semibold bg-transparent rounded-lg text-red-400 hover:bg-red-500 hover:text-white" href="{{url('/logout')}}">
                        Odhlásiť
                    </a>
                </div>
            </div>
        </div>
    @else
        <a class="{{ Route::is('login') ? 'bg-blue-600' : '' }} px-4 py-2 mt-2 text-sm font-semibold rounded-lg hover:bg-gray-700 md:mt-0 md:ml-4 transition-colors duration-200" href="{{route('login')}}">
            Prihlásenie
        </a>
        <a class="{{ Route::is('register') ? 'bg-blue-600' : '' }} px-4 py-2 mt-2 text-sm font-semibold text-white rounded-lg hover:bg-gray-700 md:mt-0 md:ml-2 transition-colors duration-200" href="{{route('register')}}">
            Registrácia
        </a>
    @endauth
</nav>
