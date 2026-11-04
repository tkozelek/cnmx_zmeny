<div x-show="openn"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="openn = false"
     class="fixed inset-0 z-[100] grid h-full w-full place-items-center bg-neutral-950/60 backdrop-blur-xl lg:hidden overflow-y-auto p-4"
     style="display: none;">

    {{-- The dialog wraps the nav: role="dialog" on the <nav> itself hid it as a navigation landmark. --}}
    <div @click.stop x-trap.noscroll.inert="openn" role="dialog" aria-modal="true" aria-label="Menu" class="relative w-full max-w-xs rounded-lg bg-neutral-900 border border-neutral-800 p-6 shadow-xl">
    <nav aria-label="Hlavné menu" class="flex w-full flex-col items-center space-y-3 text-center">

        <!-- Dedicated Close Button -->
        <button
            type="button"
            @click="openn = false"
            class="absolute top-4 right-4 flex h-9 w-9 items-center justify-center rounded-xl bg-neutral-800/80 text-neutral-400 hover:text-white hover:bg-neutral-700 transition"
            aria-label="Zavrieť menu"
            title="Zavrieť menu"
        >
            <i class="fa-solid fa-xmark text-lg"></i>
        </button>





        @if($navUser)
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
                        <span class="ml-2 inline-flex h-5 w-5 items-center justify-center rounded-full bg-amber-400 text-xs font-bold text-neutral-950 align-middle">
                            <span>{{ $newUserCount }}</span>
                            <span class="sr-only">&nbsp;čakajúcich na schválenie</span>
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

        @if($navUser)
            <div class="my-2 w-full border-t border-neutral-800"></div>

            <x-nav-link
                route="profile.index"
                :is-mobile="true"
                icon='<i class="fa-solid fa-user text-brand-400"></i>'
            >
                Môj profil
            </x-nav-link>

            @if($canManageTeam)
                <x-nav-link
                    route="team.settings.edit"
                    :is-mobile="true"
                    icon='<i class="fa-solid fa-sliders text-brand-400"></i>'
                >
                    Správa kina
                </x-nav-link>
            @endif

            @can('accessPlatform')
                <x-nav-link
                    route="filament.platform.pages.dashboard"
                    :is-mobile="true"
                    icon='<i class="fa-solid fa-shield-halved text-brand-400"></i>'
                >
                    Platforma
                </x-nav-link>
            @endcan

            @can('viewAny', \App\Models\Position::class)
                <x-nav-link
                    route="positions.index"
                    :is-mobile="true"
                    icon='<i class="fa-solid fa-list-check text-brand-400"></i>'
                >
                    Pozície
                </x-nav-link>
            @endcan

            <x-nav-link
                route="settings.index"
                :is-mobile="true"
                icon='<i class="fa-solid fa-sliders"></i>'
            >
                Nastavenia
            </x-nav-link>

            <x-logout-button
                :is-mobile="true"
                icon='<i class="fa-solid fa-right-from-bracket"></i>'
            >
                Odhlásiť sa
            </x-logout-button>
        @elseif($isGuest)
            <div class="my-2 w-full border-t border-neutral-800"></div>
            <a
                href="{{ route('login') }}"
                @if($activeGuestLink === 'login') aria-current="page" @endif
                @class([
                    'w-full rounded-lg border py-3 text-base font-semibold transition hover:bg-neutral-800',
                    'border-brand-500/40 bg-brand-500/15 text-brand-300' => $activeGuestLink === 'login',
                    'border-neutral-800 bg-neutral-900 text-neutral-200' => $activeGuestLink !== 'login',
                ])
            >Prihlásenie</a>
            <a
                href="{{ route('register') }}"
                @if($activeGuestLink === 'register') aria-current="page" @endif
                @class([
                    'w-full rounded-lg border py-3 text-base font-semibold transition',
                    'border-brand-500/40 bg-brand-500/15 text-brand-300' => $activeGuestLink === 'register',
                    'border-transparent bg-neutral-100 text-neutral-900 hover:bg-white' => $activeGuestLink !== 'register',
                ])
            >Registrácia</a>
        @endif
    </nav>
    </div>
</div>
