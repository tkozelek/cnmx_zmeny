@auth
    @php
        $user = auth()->user();
        $teams = $user->approvedTeams();
        if ($teams->isEmpty() && $user->hasRole('admin')) {
            $teams = \App\Models\Team::all();
        }
        $currentTeam = $user->currentTeam ?? $teams->first() ?? app(\App\Models\Team::class);
    @endphp

    @if($teams->count() > 1)
        <div
            x-data="{ openTeam: false }"
            @mouseenter="openTeam = true"
            @mouseleave="openTeam = false"
            @click.outside="openTeam = false"
            class="relative py-1"
        >
            <button
                @click="openTeam = !openTeam"
                type="button"
                class="flex items-center gap-2 rounded-lg bg-neutral-800/80 border border-neutral-700/80 px-3 py-1.5 text-sm font-semibold text-neutral-200 transition hover:bg-neutral-800 hover:text-white focus:outline-none"
                title="Prepnutie kina"
            >
                <i class="fa-solid fa-film text-xs text-sky-400"></i>
                <span class="truncate max-w-[130px] sm:max-w-[160px]">{{ $currentTeam?->name ?? 'Kino' }}</span>
                <i class="fa-solid fa-chevron-down text-xs text-neutral-500 transition-transform duration-200" :class="{'rotate-180': openTeam}"></i>
            </button>

            <div
                x-cloak
                x-show="openTeam"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                class="absolute left-1/2 -translate-x-1/2 sm:translate-x-0 sm:right-0 sm:left-auto top-full pt-1 z-50 w-56"
                style="display: none;"
            >






                <div class="rounded-xl border border-neutral-800 bg-neutral-900 p-1.5 shadow-2xl">
                    <div class="px-3 py-2 border-b border-neutral-800 mb-1">
                        <p class="text-xs font-medium text-neutral-400">Aktívne kino</p>
                        <p class="truncate text-sm font-semibold text-sky-400">{{ $currentTeam?->name }}</p>
                    </div>

                    <div class="flex flex-col gap-0.5 max-h-60 overflow-y-auto">
                        @foreach($teams as $team)
                            @if($team->id !== $currentTeam?->id)
                                <form method="POST" action="{{ route('teams.switch', $team) }}">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-medium text-neutral-300 transition hover:bg-neutral-800 hover:text-white"
                                    >
                                        <span class="truncate">{{ $team->name }}</span>
                                        <i class="fa-solid fa-arrow-right-to-bracket text-xs text-neutral-500"></i>
                                    </button>
                                </form>
                            @else
                                <div class="flex w-full items-center justify-between rounded-lg bg-sky-500/10 px-3 py-2 text-sm font-semibold text-sky-300 border border-sky-500/30">
                                    <span class="truncate">{{ $team->name }}</span>
                                    <i class="fa-solid fa-check text-xs text-sky-400"></i>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif
@endauth
