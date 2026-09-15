<div x-show="openn"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="openn = false"
     class="fixed inset-0 z-[100] grid h-full w-full place-items-center bg-neutral-950/60 backdrop-blur-xl md:hidden overflow-y-auto p-4"
     style="display: none;">

    <nav @click.stop class="relative flex w-full max-w-xs flex-col items-center space-y-3 rounded-lg bg-neutral-900 border border-neutral-800 p-6 text-center shadow-xl">

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

            <x-nav-link
                route="calendar.index"
                :is-mobile="true"
                icon='<i class="fa-solid fa-calendar-week"></i>'
            >
                Týždne
            </x-nav-link>

            @can('viewAny', \App\Models\User::class)
                <x-nav-link
                    route="admin.users.index"
                    :is-mobile="true"
                    icon='<i class="fa-solid fa-users"></i>'
                >
                    Používatelia
                    @if(isset($newUserCount) && $newUserCount > 0)
                        <span class="ml-2 inline-flex h-5 w-5 items-center justify-center rounded-full bg-rose-500 text-xs font-bold text-white align-middle" aria-label="Čaká na schválenie: {{ $newUserCount }}">
                            <span aria-hidden="true">{{ $newUserCount }}</span>
                            <span class="sr-only">čakajúcich na schválenie</span>
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

            <x-nav-link
                route="profile.index"
                :is-mobile="true"
                icon='<i class="fa-solid fa-user text-indigo-400"></i>'
            >
                Môj profil
            </x-nav-link>

            @can('viewSettings', app(\App\Models\Team::class))
                <x-nav-link
                    route="team.settings.edit"
                    :is-mobile="true"
                    icon='<i class="fa-solid fa-sliders text-brand-400"></i>'
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
        @elseif(! auth()->check())
            <div class="my-2 w-full border-t border-neutral-800"></div>
            <a class="w-full rounded-lg bg-neutral-900 border border-neutral-800 py-3 text-base font-semibold text-neutral-200 transition hover:bg-neutral-800" href="{{ route('login') }}">Prihlásenie</a>
            <a class="w-full rounded-lg bg-neutral-100 py-3 text-base font-semibold text-neutral-900 transition hover:bg-white" href="{{ route('register') }}">Registrácia</a>
        @endif
    </nav>
</div>
