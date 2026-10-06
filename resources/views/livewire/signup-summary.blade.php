<div>
    @if($this->signupCounts->isNotEmpty())
        @php
            $totalSignups = $this->signupCounts->sum('count');
            $userCount = $this->signupCounts->count();
        @endphp

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
                        {{ $userCount }} {{ $userCount === 1 ? 'človek' : ($userCount < 5 ? 'ľudia' : 'ľudí') }} · {{ $totalSignups }} {{ $totalSignups === 1 ? 'deň' : ($totalSignups < 5 ? 'dni' : 'dní') }}
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
                            @php
                                $isCurrentUser = auth()->id() === $row->user_id;
                            @endphp
                            <tr @class([
                                'transition-colors duration-150',
                                'bg-neutral-800/40' => $isCurrentUser,
                                'hover:bg-neutral-800/60' => true,
                            ])>
                                <td class="px-4 py-2.5 text-center text-xs text-neutral-400 font-medium">
                                    {{ $index + 1 }}
                                </td>
                                <td class="px-4 py-2.5 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="truncate text-sm {{ $isCurrentUser ? 'text-white font-semibold' : 'text-neutral-200' }}">
                                            {{ $row->lastname }} {{ Str::substr($row->name, 0, 1) }}.
                                        </span>
                                        @if($isCurrentUser)
                                            <span class="text-xs text-neutral-400">(ja)</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-2.5 text-right">
                                    <span class="text-sm font-semibold tabular-nums text-neutral-200">
                                        {{ $row->count }} {{ $row->count === 1 ? 'deň' : ($row->count < 5 ? 'dni' : 'dní') }}
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
