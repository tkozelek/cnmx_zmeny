<div x-show="open"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-40 flex h-screen w-screen items-center justify-center bg-slate-900/95 backdrop-blur-sm md:hidden"
     @click.away="open = false"
     style="display: none;">
    <nav class="flex w-full flex-col items-center space-y-3 p-8 text-center">
        @auth
            @if(auth()->user()->hasRole(3))
                <a class="relative w-full rounded-lg py-3 text-xl font-semibold transition-colors duration-200 hover:bg-gray-800" href="{{route('admin.users.index')}}">
                    Používatelia
                    @if(isset($newUserCount) && $newUserCount > 0)
                        <span class="absolute top-1/2 right-1/4 -translate-y-1/2 flex h-5 w-5">
                            <span class="relative inline-flex items-center justify-center rounded-full h-5 w-5 bg-red-500 text-xs text-white">
                                {{ $newUserCount }}
                            </span>
                        </span>
                    @endif
                </a>
            @endif
            <a class="w-full rounded-lg py-3 text-xl font-semibold transition-colors duration-200 hover:bg-gray-800" href="{{ route('holiday.index') }}">Absencia</a>
        @endauth

        <a class="{{ Route::is('help') ? 'bg-blue-600' : '' }} flex w-full items-center justify-center rounded-lg py-3 text-xl font-semibold transition-colors duration-200 hover:bg-gray-800" href="{{ route('help') }}">Pomoc</a>

        @auth
            <a class="flex w-full items-center justify-center rounded-lg py-3 text-xl font-semibold transition-colors duration-200 hover:bg-gray-800" href="{{ route('bugreport.index') }}">Nahlásiť chybu</a>
            <div class="my-2 w-1/2 border-t border-gray-700"></div>
            <a class="w-full rounded-lg py-3 text-xl font-semibold transition-colors duration-200 hover:bg-gray-800" href="{{route('settings.password')}}">Zmena hesla</a>
            <a class="w-full rounded-lg py-3 text-xl font-semibold text-red-400 transition-colors duration-200 hover:bg-red-500 hover:text-white" href="{{ url('/logout') }}">Odhlásiť</a>
        @else
            <div class="my-2 w-1/2 border-t border-gray-700"></div>
            <a class="{{ Route::is('login') ? 'bg-blue-600' : '' }} w-full rounded-lg py-3 text-xl font-semibold transition-colors duration-200 hover:bg-gray-800" href="{{ route('login') }}">Prihlásenie</a>
            <a class="{{ Route::is('register') ? 'bg-blue-600' : '' }} mt-2 w-full rounded-lg py-3 text-xl font-semibold text-white transition-colors duration-200 hover:bg-gray-800" href="{{ route('register') }}">Registrácia</a>
        @endauth
    </nav>
</div>
