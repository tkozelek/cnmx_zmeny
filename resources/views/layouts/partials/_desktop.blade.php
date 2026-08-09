<nav class="hidden md:flex items-center gap-2">
    @auth
        @can('viewAny', \App\Models\User::class)
            <x-nav-link
                route="admin.users.index"
                icon='<i class="fa-solid fa-users text-sm"></i>'
            >
                Používatelia
                @if(isset($newUserCount) && $newUserCount > 0)
                    <span class="ml-1.5 inline-flex items-center justify-center h-4 min-w-4 px-1.5 text-xs font-bold text-white bg-rose-500 rounded-full align-middle">
                        {{ $newUserCount }}
                    </span>
                @endif
            </x-nav-link>
        @endcan

        <x-nav-link
            route="absences.index"
            icon='<i class="fa-solid fa-calendar-days text-sm"></i>'
        >
            Absencie
        </x-nav-link>
    @endauth

    <x-nav-link
        route="help"
        icon='<i class="fa-solid fa-circle-question text-sm"></i>'
    >
        Pomoc
    </x-nav-link>

    @auth
        <x-team-switcher />

        {{-- High-Contrast Vertical Separator --}}
        <div class="h-6 w-px bg-neutral-700/80 mx-3 self-center"></div>

        <div
            x-data="{ openProfile: false }"
            @mouseenter="openProfile = true"
            @mouseleave="openProfile = false"
            @click.outside="openProfile = false"
            class="relative py-1"
        >
            <button
                type="button"
                @click="openProfile = !openProfile"
                class="flex items-center gap-2.5 px-3 py-1.5 text-base font-semibold text-neutral-200 transition hover:text-white focus:outline-none"
            >
                <i class="fa-solid fa-user text-sm text-neutral-400"></i>
                <span>{{ auth()->user()->name }} {{ auth()->user()->lastname }}</span>
                <i class="fa-solid fa-chevron-down text-xs text-neutral-500 transition-transform duration-200" :class="{'rotate-180': openProfile}"></i>
            </button>

            {{-- Dropdown Container --}}
            <div
                x-cloak
                x-show="openProfile"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                class="absolute right-0 top-full pt-1 z-50 w-56"
                style="display: none;"
            >






                <div class="rounded-xl border border-neutral-800 bg-neutral-900 p-1.5 shadow-2xl">
                    <div class="px-3 py-2 border-b border-neutral-800 mb-1">
                        <p class="text-xs font-medium text-neutral-400">Prihlásený ako</p>
                        <p class="truncate text-sm font-semibold text-neutral-100">{{ auth()->user()->name }} {{ auth()->user()->lastname }}</p>
                    </div>

                    @can('viewSettings', app(\App\Models\Team::class))
                        <a
                            href="{{ route('team.settings.edit') }}"
                            class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-neutral-300 transition hover:bg-neutral-800 hover:text-white"
                        >
                            <i class="fa-solid fa-sliders text-xs text-sky-400"></i>
                            Správa kina
                        </a>
                    @endcan

                    @can('viewAny', \App\Models\Position::class)
                        <a
                            href="{{ route('positions.index') }}"
                            class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-neutral-300 transition hover:bg-neutral-800 hover:text-white"
                        >
                            <i class="fa-solid fa-list-check text-xs text-sky-400"></i>
                            Pozície
                        </a>
                    @endcan

                    <a
                        href="{{ route('hours.index') }}"
                        class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-neutral-300 transition hover:bg-neutral-800 hover:text-white"
                    >
                        <i class="fa-solid fa-clock text-xs text-neutral-400"></i>
                        Evidencia hodín
                    </a>

                    <a
                        href="{{ route('settings.password.edit') }}"
                        class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-neutral-300 transition hover:bg-neutral-800 hover:text-white"
                    >
                        <i class="fa-solid fa-key text-xs text-neutral-400"></i>
                        Zmena hesla
                    </a>

                    <div class="my-1 border-t border-neutral-800"></div>


                    <x-logout-button icon='<i class="fa-solid fa-right-from-bracket text-xs"></i>'>
                        Odhlásiť sa
                    </x-logout-button>
                </div>
            </div>
        </div>
    @else
        <div class="flex items-center gap-2 ml-2">
            <a
                href="{{ route('login') }}"
                class="rounded-lg px-4 py-2 text-base font-semibold text-neutral-300 transition hover:bg-neutral-800 hover:text-white"
            >
                Prihlásenie
            </a>
            <a
                href="{{ route('register') }}"
                class="rounded-lg bg-neutral-100 px-4 py-2 text-base font-semibold text-neutral-900 transition hover:bg-white"
            >
                Registrácia
            </a>
        </div>
    @endauth
</nav>
