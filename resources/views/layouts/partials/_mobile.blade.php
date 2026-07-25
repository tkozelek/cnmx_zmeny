<div x-show="openn"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 flex h-screen w-screen items-center justify-center bg-neutral-950/95 backdrop-blur-md md:hidden"
     @click.away="openn = false"
     style="display: none;">

    {{-- Large Close (X) Button in top right --}}
    <button
        type="button"
        @click="openn = false"
        class="absolute top-5 right-5 flex h-12 w-12 items-center justify-center rounded-full bg-neutral-900 border border-neutral-800 text-neutral-300 transition hover:bg-neutral-800 hover:text-white focus:outline-none shadow-lg"
        aria-label="Zavrieť menu"
        title="Zavrieť menu"
    >
        <i class="fa-solid fa-xmark text-2xl"></i>
    </button>

    <nav class="flex w-full max-w-sm flex-col items-center space-y-3 p-6 text-center">
        @auth
            <div class="w-full mb-2 flex justify-center">
                <x-team-switcher />
            </div>

            @if(auth()->user()->hasRole('admin'))
                <x-nav-link
                    route="admin.users.index"
                    :is-mobile="true"
                    icon='<i class="fa-solid fa-users"></i>'
                >
                    Používatelia
                    @if(isset($newUserCount) && $newUserCount > 0)
                        <span class="ml-2 inline-flex h-5 w-5 items-center justify-center rounded-full bg-rose-500 text-xs font-bold text-white align-middle">
                            {{ $newUserCount }}
                        </span>
                    @endif
                </x-nav-link>
            @endif

            <x-nav-link
                route="absences.index"
                :is-mobile="true"
                icon='<i class="fa-solid fa-calendar-days"></i>'
            >
                Absencie
            </x-nav-link>
        @endauth

        <x-nav-link
            route="help"
            :is-mobile="true"
            icon='<i class="fa-solid fa-circle-question"></i>'
        >
            Pomoc
        </x-nav-link>

        @auth
            <div class="my-2 w-full border-t border-neutral-800"></div>

            <x-nav-link
                route="hours.index"
                :is-mobile="true"
                icon='<i class="fa-solid fa-clock"></i>'
            >
                Evidencia hodín
            </x-nav-link>

            <x-nav-link
                route="settings.password.edit"
                :is-mobile="true"
                icon='<i class="fa-solid fa-key"></i>'
            >
                Zmena hesla
            </x-nav-link>

            <x-logout-button
                :is-mobile="true"
                icon='<i class="fa-solid fa-right-from-bracket"></i>'
            >
                Odhlásiť sa
            </x-logout-button>
        @else
            <div class="my-2 w-full border-t border-neutral-800"></div>
            <a class="w-full rounded-lg bg-neutral-900 border border-neutral-800 py-3 text-base font-semibold text-neutral-200 transition hover:bg-neutral-800" href="{{ route('login') }}">Prihlásenie</a>
            <a class="w-full rounded-lg bg-neutral-100 py-3 text-base font-semibold text-neutral-900 transition hover:bg-white" href="{{ route('register') }}">Registrácia</a>
        @endauth
    </nav>
</div>
