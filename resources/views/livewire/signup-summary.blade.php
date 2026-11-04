<div>
    @if($this->signupCounts->isNotEmpty())
        <section class="rounded-lg border border-neutral-800 bg-neutral-900 shadow-sm overflow-hidden flex flex-col">
            {{-- Card Header --}}
            <div class="px-4 py-3 border-b border-neutral-800 bg-neutral-950 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5 min-w-0">
                    <i class="fa-solid fa-chart-simple text-neutral-400 text-xs shrink-0"></i>
                    <div>
                        <h2 class="text-sm font-semibold text-neutral-100 truncate">Prehľad počtu zapísaných</h2>
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <i wire:loading class="fa-solid fa-circle-notch fa-spin text-neutral-400 text-xs" aria-hidden="true"></i>
                    <span class="text-xs text-neutral-400">
                        {{ $totalsLabel }}
                    </span>
                </div>
            </div>

            {{-- Table Container with custom scrollbar and sticky header --}}
            <div wire:loading.class="blur-sm opacity-60 pointer-events-none" class="relative lg:max-h-[380px] lg:overflow-y-auto custom-scrollbar transition-[filter,opacity] duration-200">
                <table class="w-full text-left text-sm text-neutral-300">
                    <thead class="sticky top-0 z-10 border-b border-neutral-800 bg-neutral-950 text-xs font-medium uppercase tracking-wider text-neutral-400">
                        <tr>
                            <th class="px-4 py-2 w-10 text-center">#</th>
                            <th class="px-4 py-2">Meno</th>
                            <th class="px-4 py-2 text-right w-28">Dni</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-800">
                        @foreach($this->signupCounts as $index => $row)
                            <tr @class([
                                'transition-colors duration-150',
                                'bg-neutral-800/40' => $row->user_id === $viewerId,
                                'hover:bg-neutral-800/60' => true,
                            ])>
                                <td class="px-4 py-2.5 text-center text-xs text-neutral-400 font-medium">
                                    {{ $index + 1 }}
                                </td>
                                <td class="px-4 py-2.5 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="truncate text-sm {{ $row->user_id === $viewerId ? 'text-white font-semibold' : 'text-neutral-200' }}">
                                            {{ $row->lastname }} {{ Str::substr($row->name, 0, 1) }}.
                                        </span>
                                        @if($row->user_id === $viewerId)
                                            <span class="text-xs text-neutral-400">(ja)</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-2.5 text-right">
                                    <span class="text-sm font-semibold tabular-nums text-neutral-200">
                                        {{ $this->daysLabel($row->count) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @else
        <div class="rounded-lg border border-neutral-800 bg-neutral-900 shadow-sm p-6 text-center">
            <div class="flex flex-col items-center justify-center py-4 text-neutral-400">
                <i class="fa-solid fa-user-clock text-xl text-neutral-400 mb-1.5"></i>
                <p class="text-xs font-medium text-neutral-300">Žiadne zápisy na tento týždeň</p>
                <p class="text-xs text-neutral-400 mt-0.5">Hneď ako sa niekto zapíše, prehľad sa zobrazí tu.</p>
            </div>
        </div>
    @endif
</div>
