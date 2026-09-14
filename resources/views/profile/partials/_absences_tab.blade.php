<div x-show="tab === 'absences'" class="p-3.5 sm:p-6 space-y-5 sm:space-y-8" style="display: none;">
    {{-- Active Absences --}}
    <div>
        <div class="flex items-center justify-between gap-2 mb-3">
            <h3 class="text-sm sm:text-base font-semibold text-white flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-xs text-emerald-400"></i>
                Aktívne a nadchádzajúce absencie
            </h3>
            @if(auth()->id() === $user->id)
                <a href="{{ route('absences.index') }}" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 transition flex items-center gap-1 shrink-0">
                    <i class="fa-solid fa-plus text-[10px]"></i>
                    <span>Nová</span>
                </a>
            @endif
        </div>

        <div class="space-y-2">
            @forelse ($activeAbsences as $absence)
                <div class="rounded-lg border border-neutral-800 bg-neutral-950 p-3 sm:p-4 flex flex-col sm:flex-row justify-between sm:items-center gap-2.5 sm:gap-3 hover:border-neutral-700/80 transition">
                    <div class="min-w-0">
                        <p class="font-semibold text-neutral-100 text-xs sm:text-sm truncate">
                            {{ $absence->reason ?: 'Bez udaného dôvodu' }}
                        </p>
                        <div class="flex items-center gap-2 text-xs text-neutral-400 mt-0.5 sm:mt-1">
                            <i class="fa-regular fa-calendar text-neutral-400 text-[10px]"></i>
                            <span>{{ $absence->date_from->format('d.m.Y') }} &ndash; {{ $absence->isOpenEnded() ? 'Neurčito' : $absence->date_to->format('d.m.Y') }}</span>
                            @if($absence->isRecurring())
                                <span class="text-neutral-400">&bull;</span>
                                <span class="text-indigo-400 font-medium">Opakujúca sa</span>
                            @endif
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 self-start sm:self-center shrink-0">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        Aktívna
                    </span>
                </div>
            @empty
                <x-empty-state class="py-6 px-3" icon="fa-calendar-check" message="Žiadne aktívne absencie">
                    Používateľ nemá žiadne prebiehajúce ani nahlásené budúce absencie.
                </x-empty-state>
            @endforelse
        </div>
    </div>

    <div class="border-t border-neutral-800"></div>

    {{-- Inactive / History Absences --}}
    <div>
        <h3 class="text-sm sm:text-base font-semibold text-white flex items-center gap-2 mb-3">
            <i class="fa-solid fa-clock-rotate-left text-xs text-neutral-400"></i>
            História predchádzajúcich absencií
        </h3>

        <div class="space-y-2">
            @forelse ($inactiveAbsences as $absence)
                <div class="rounded-lg border border-neutral-800 bg-neutral-950 p-3 sm:p-4 flex flex-col sm:flex-row justify-between sm:items-center gap-2.5 sm:gap-3">
                    <div class="min-w-0">
                        <p class="font-medium text-neutral-300 text-xs sm:text-sm truncate">
                            {{ $absence->reason ?: 'Bez udaného dôvodu' }}
                        </p>
                        <div class="flex items-center gap-2 text-xs text-neutral-400 mt-0.5 sm:mt-1">
                            <i class="fa-regular fa-calendar text-neutral-400 text-[10px]"></i>
                            <span>{{ $absence->date_from->format('d.m.Y') }} &ndash; {{ $absence->date_to->format('d.m.Y') }}</span>
                        </div>
                    </div>
                    @if($absence->status === \App\Enums\AbsenceStatus::Cancelled)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-rose-500/10 text-rose-400 border border-rose-500/20 self-start sm:self-center shrink-0">
                            Zrušená
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-neutral-800 text-neutral-400 border border-neutral-700/60 self-start sm:self-center shrink-0">
                            Ukončená
                        </span>
                    @endif
                </div>
            @empty
                <p class="text-xs sm:text-sm text-neutral-400 py-3 text-center">Žiadna história predchádzajúcich absencií.</p>
            @endforelse

            @if ($inactiveAbsences->hasPages())
                <div class="pt-3">
                    {{ $inactiveAbsences->links('vendor.pagination.tailwind') }}
                </div>
            @endif
        </div>
    </div>
</div>
