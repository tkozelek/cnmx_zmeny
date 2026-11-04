<div
    x-data="{ openTeam: false }"
    @click.outside="openTeam = false"
    @keydown.escape="if (openTeam) { openTeam = false; $refs.teamButton.focus() }"
    @focusout="if (! $el.contains($event.relatedTarget)) openTeam = false"
    class="relative py-1"
>
    <button
        x-ref="teamButton"
        @click="openTeam = !openTeam"
        :aria-expanded="openTeam.toString()"
        aria-label="Prepnúť kino, aktuálne {{ $currentTeam?->name ?? 'Kino' }}"
        type="button"
        class="flex items-center gap-2 rounded-lg bg-neutral-800/80 border border-neutral-700/80 px-3 py-1.5 text-sm font-semibold text-neutral-200 transition hover:bg-neutral-800 hover:text-white focus:outline-none"
        title="Prepnutie kina"
    >
        <i class="fa-solid fa-film text-xs text-brand-400" aria-hidden="true"></i>
        <span class="truncate max-w-[130px] sm:max-w-[160px]">{{ $currentTeam?->name ?? 'Kino' }}</span>
        <i class="fa-solid fa-chevron-down text-xs text-neutral-400 transition-transform duration-200" :class="{'rotate-180': openTeam}" aria-hidden="true"></i>
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
                <p class="truncate text-sm font-semibold text-brand-400">{{ $currentTeam?->name }}</p>
            </div>

            <div class="flex flex-col gap-0.5 max-h-60 overflow-y-auto">
                @foreach($teams as $team)
                    @if($team->id !== $currentTeam?->id)
                        <form method="POST" action="{{ route('teams.switch', $team) }}">
                            @csrf
                            <x-submit-button variant="custom" busy-label="Prepínam…" class="w-full rounded-lg px-3 py-2 text-left text-sm !font-medium text-neutral-300 hover:bg-neutral-800 hover:text-white [&>span]:w-full">
                                <span class="flex w-full items-center justify-between gap-2">
                                    <span class="truncate">{{ $team->name }}</span>
                                    <i class="fa-solid fa-arrow-right-to-bracket text-xs text-neutral-400" aria-hidden="true"></i>
                                </span>
                            </x-submit-button>
                        </form>
                    @else
                        <div class="flex w-full items-center justify-between rounded-lg bg-brand-500/10 px-3 py-2 text-sm font-semibold text-brand-300 border border-brand-500/60">
                            <span class="truncate">{{ $team->name }}</span>
                            <i class="fa-solid fa-check text-xs text-brand-400"></i>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
</div>
