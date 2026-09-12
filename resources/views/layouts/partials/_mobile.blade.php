<div x-show="openn"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="openn = false"
     class="fixed inset-0 z-[100] flex h-full w-full flex-col items-center justify-center bg-neutral-950/60 backdrop-blur-xl md:hidden overflow-y-auto p-4"
     style="display: none;">

    <nav @click.stop class="relative flex w-full max-w-xs flex-col items-center space-y-3 rounded-2xl bg-neutral-900/90 backdrop-blur-md border border-neutral-700/60 p-6 text-center shadow-2xl my-auto">

        <!-- Dedicated Close Button -->
        <button
            type="button"
            @click="openn = false"
            class="absolute top-4 right-4 flex h-9 w-9 items-center justify-center rounded-xl bg-neutral-800/80 text-neutral-400 hover:text-white hover:bg-neutral-750 transition focus:outline-none"
            aria-label="Zavrieť menu"
            title="Zavrieť menu"
        >
            <i class="fa-solid fa-xmark text-lg"></i>
        </button>





        @if(auth()->check() && auth()->user()->hasVerifiedEmail())
            <div class="w-full mb-2 flex justify-center">
                <x-team-switcher />
            </div>

            @can('viewAny', \App\Models\User::class)
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
            @endcan

            <x-nav-link
                route="absences.index"
                :is-mobile="true"
                icon='<i class="fa-solid fa-calendar-days"></i>'
            >
                Absencie
            </x-nav-link>
        @endif

        <x-nav-link
            route="help"
            :is-mobile="true"
            icon='<i class="fa-solid fa-circle-question"></i>'
        >
            Pomoc
        </x-nav-link>

        @if(auth()->check() && auth()->user()->hasVerifiedEmail())
            <div class="my-2 w-full border-t border-neutral-800"></div>

            @can('viewSettings', app(\App\Models\Team::class))
                <x-nav-link
                    route="team.settings.edit"
                    :is-mobile="true"
                    icon='<i class="fa-solid fa-sliders text-sky-400"></i>'
                >
                    Správa kina
                </x-nav-link>
            @endcan

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
        @endif
    </nav>
</div>
