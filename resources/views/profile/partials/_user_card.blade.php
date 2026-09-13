<div class="rounded-lg border border-neutral-800 bg-neutral-900 shadow-sm p-4 sm:p-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 sm:gap-5">
        {{-- User Info & Metadata --}}
        <div class="space-y-2 sm:space-y-2.5 min-w-0">
            <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                <h2 class="text-xl sm:text-2xl font-bold text-white tracking-tight truncate">{{ $user->name }} {{ $user->lastname }}</h2>

                @php
                    $role = \App\Enums\Role::tryFrom($user->getRoleNames()->first() ?? '');
                    [$roleBg, $roleText, $roleBorder] = match($role) {
                        \App\Enums\Role::HeadManager => ['bg-indigo-500/10', 'text-indigo-400', 'border-indigo-500/20'],
                        \App\Enums\Role::Manager => ['bg-sky-500/10', 'text-sky-400', 'border-sky-500/20'],
                        default => ['bg-emerald-500/10', 'text-emerald-400', 'border-emerald-500/20'],
                    };
                @endphp
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $roleBg }} {{ $roleText }} {{ $roleBorder }}">
                    {{ $role?->label() ?? 'Brigádnik' }}
                </span>

                @if($user->is_active ?? true)
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium border bg-emerald-500/10 text-emerald-400 border-emerald-500/20">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                        Aktívny
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border bg-neutral-800 text-neutral-400 border-neutral-700">
                        Neaktívny
                    </span>
                @endif
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
                        <span class="text-neutral-500">Zatiaľ neprihlásený</span>
                    @endif
                </span>
            </div>
        </div>

        {{-- Actions --}}
        @if(auth()->id() === $user->id || auth()->user()->can('update', $user))
            <div class="grid grid-cols-2 md:flex md:items-center gap-2 pt-1 md:pt-0 shrink-0 w-full md:w-auto">
                @if(auth()->id() === $user->id)
                    <a href="{{ route('settings.password.edit') }}" class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-neutral-800 hover:bg-neutral-750 text-neutral-200 hover:text-white border border-neutral-700/60 px-3 py-2 text-xs font-semibold transition text-center">
                        <i class="fa-solid fa-key text-[11px] text-neutral-400"></i>
                        <span>Zmeniť heslo</span>
                    </a>
                    <a href="{{ route('absences.index') }}" class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white px-3 py-2 text-xs font-semibold transition shadow-sm text-center">
                        <i class="fa-solid fa-plus text-[11px]"></i>
                        <span>Absencia</span>
                    </a>
                @elseif(auth()->user()->can('update', $user))
                    <a href="{{ route('admin.users.edit', $user) }}" class="col-span-2 md:col-auto inline-flex items-center justify-center gap-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white px-3.5 py-2 text-xs font-semibold transition shadow-sm text-center">
                        <i class="fa-solid fa-user-pen text-[11px]"></i>
                        <span>Upraviť používateľa</span>
                    </a>
                @endif
            </div>
        @endif
    </div>
</div>
