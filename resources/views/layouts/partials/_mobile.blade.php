@php
    $activeClass = 'bg-blue-700';
@endphp

<div x-show="openn"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-40 flex h-screen w-screen items-center justify-center bg-slate-900/95 backdrop-blur-sm md:hidden"
     @click.away="openn = false"
     style="display: none;">
    <nav class="flex w-full flex-col items-center space-y-3 p-8 text-center">
        @auth
            @if(auth()->user()->isAdmin())
                <x-nav-link
                    class="relative"
                    route="admin.users.index"
                    :is-mobile="true"
                    icon='<i class="fa-solid fa-users"></i>'
                >
                    Používatelia
                    @if(isset($newUserCount) && $newUserCount > 0)
                        <span class="animate-ping absolute inline-flex w-6 h-6 text-xs font-bold bg-red-500 border-2 border-gray-900 rounded-full -top-2"></span>
                        <div class="absolute inline-flex items-center justify-center w-6 h-6 text-xs font-bold text-white bg-red-500 border-2 rounded-full -top-2 border-gray-900">
                            <span>
                                {{ $newUserCount }}
                            </span>
                        </div>
                    @endif
                </x-nav-link>
            @endif
            <x-nav-link
                route="holiday.index"
                :is-mobile="true"
                icon='<i class="fa-solid fa-calendar-days"></i>'
            >
                Absencia
            </x-nav-link>
        @endauth

        <x-nav-link
            route="help"
            :is-mobile="true"
            icon='<i class="fa-solid fa-circle-question"></i>'
        >
            Nahlásiť chybu
        </x-nav-link>

        @auth
            <x-nav-link
                route="bugreport.index"
                :is-mobile="true"
                icon='<i class="fa-solid fa-bug"></i>'
            >
                Nahlásiť chybu
            </x-nav-link>
            <x-divider/>
            <x-nav-link
                route="settings.password"
                :is-mobile="true"
                icon='<i class="fa-solid fa-key"></i>'
            >
                Zmena hesla
            </x-nav-link>
            <x-nav-link
                class="!text-red-400 hover:!bg-red-500 hover:!text-white"
                route="logout"
                :is-mobile="true"
                icon='<i class="fa-solid fa-right-from-bracket"></i>'
            >
                Odhlásenie
            </x-nav-link>
        @else
            <x-divider/>
            <a class="{{ Route::is('login') ? 'bg-blue-600' : '' }} w-full rounded-lg py-3 text-xl font-semibold transition-colors duration-200 hover:bg-gray-800" href="{{ route('login') }}">Prihlásenie</a>
            <a class="{{ Route::is('register') ? 'bg-blue-600' : '' }} mt-2 w-full rounded-lg py-3 text-xl font-semibold text-white transition-colors duration-200 hover:bg-gray-800" href="{{ route('register') }}">Registrácia</a>
        @endauth
    </nav>
</div>
