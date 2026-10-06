<div class="rounded-lg border border-neutral-800 bg-neutral-900 shadow-sm p-4 sm:p-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 sm:gap-5">
        {{-- User Info & Metadata --}}
        <div class="space-y-2 sm:space-y-2.5 min-w-0">
            <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                <h2 class="text-xl sm:text-2xl font-bold text-white tracking-tight truncate">{{ $user->name }} {{ $user->lastname }}</h2>

                @php $role = \App\Enums\Role::tryFrom($user->getRoleNames()->first() ?? '') ?? \App\Enums\Role::Employee; @endphp
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $role->badgeClasses() }}">
                    {{ $role->label() }}
                </span>

                {{-- Active is the normal state - only the exception gets a badge. --}}
                @unless($user->is_active ?? true)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border bg-rose-500/10 text-rose-300 border-rose-500/30">
                        Neaktívny
                    </span>
                @endunless
            </div>

            <div class="flex flex-wrap items-center gap-x-4 sm:gap-x-6 gap-y-1 text-xs text-neutral-400">
                <a href="mailto:{{ $user->email }}" class="text-neutral-300 hover:text-white transition truncate max-w-full">
                    {{ $user->email }}
                </a>

                @if($user->teams->isNotEmpty())
                    <span class="truncate">
                        Kino: <span class="text-neutral-200 font-medium">{{ $user->teams->first()->name }}</span>
                    </span>
                @endif

                <span class="shrink-0">
                    Členom od: <span class="text-neutral-200 font-medium">{{ $user->created_at->format('d. m. Y') }}</span>
                </span>

                <span class="shrink-0">
                    Posledné prihlásenie:
                    @if($user->last_login_at)
                        <span class="text-neutral-200 font-medium" title="{{ $user->last_login_at->format('d.m.Y H:i:s') }}">
                            {{ $user->last_login_at->format('d.m.Y H:i') }}
                        </span>
                    @else
                        <span class="text-neutral-400">Zatiaľ neprihlásený</span>
                    @endif
                </span>
            </div>
        </div>

        {{-- Actions --}}
        @if(auth()->id() === $user->id || auth()->user()->can('update', $user))
            <div class="grid grid-cols-2 md:flex md:items-center gap-2 pt-1 md:pt-0 shrink-0 w-full md:w-auto">
                @if(auth()->id() === $user->id)
                    <a href="{{ route('settings.password.edit') }}" class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-neutral-700 bg-neutral-800 hover:bg-neutral-700 text-neutral-100 px-3 py-2 text-sm font-semibold transition text-center">
                        <i class="fa-solid fa-key text-xs text-neutral-400" aria-hidden="true"></i>
                        <span>Zmeniť heslo</span>
                    </a>
                    <a href="{{ route('absences.index') }}" class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-neutral-700 bg-neutral-800 hover:bg-neutral-700 text-neutral-100 px-3 py-2 text-sm font-semibold transition text-center">
                        <i class="fa-solid fa-calendar-xmark text-xs text-neutral-400" aria-hidden="true"></i>
                        <span>Absencie</span>
                    </a>
                @elseif(auth()->user()->can('update', $user))
                    <a href="{{ route('admin.users.edit', $user) }}" class="col-span-2 md:col-auto inline-flex items-center justify-center gap-1.5 rounded-lg bg-neutral-100 hover:bg-white text-neutral-900 px-3.5 py-2 text-sm font-semibold transition text-center">
                        <i class="fa-solid fa-user-pen text-xs" aria-hidden="true"></i>
                        <span>Upraviť používateľa</span>
                    </a>
                @endif
            </div>
        @endif
    </div>
</div>
