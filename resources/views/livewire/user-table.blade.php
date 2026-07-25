<div>
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="bg-neutral-900 border border-neutral-800 shadow-2xl rounded-xl overflow-hidden">

            {{-- Filter & Search Header Bar --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 bg-neutral-900/80 border-b border-neutral-800">
                {{-- Role Dropdown --}}
                <div x-data="{ open: false }" @click.away="open = false" class="relative">
                    <button
                        @click="open = !open"
                        type="button"
                        class="inline-flex h-11 items-center gap-2 rounded-lg bg-neutral-800 border border-neutral-700 px-4 text-sm font-semibold text-neutral-200 transition hover:bg-neutral-700 hover:text-white focus:outline-none"
                    >
                        <i class="fa-solid fa-filter text-xs text-neutral-400"></i>
                        <span>
                            @if(blank($selectedRole))
                                Všetky role
                            @else
                                {{ \App\Enums\Role::tryFrom($selectedRole)?->label() ?? $selectedRole }}
                            @endif
                        </span>
                        <i class="fa-solid fa-chevron-down text-xs text-neutral-500 transition-transform" :class="{'rotate-180': open}"></i>
                    </button>

                    <div
                        x-show="open"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="absolute left-0 top-full z-50 mt-1.5 w-56 rounded-xl border border-neutral-800 bg-neutral-900 p-2 shadow-2xl"
                        style="display: none;"
                    >
                        <button
                            type="button"
                            wire:click="$set('selectedRole', '')"
                            @click="open = false"
                            @class([
                                'flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-medium transition',
                                'bg-neutral-800 text-white font-semibold' => blank($selectedRole),
                                'text-neutral-300 hover:bg-neutral-800 hover:text-white' => !blank($selectedRole),
                            ])
                        >
                            <span>Všetky role</span>
                            @if(blank($selectedRole))
                                <i class="fa-solid fa-check text-xs text-sky-400"></i>
                            @endif
                        </button>

                        @foreach($roles as $role)
                            <button
                                type="button"
                                wire:click="$set('selectedRole', '{{ $role->value }}')"
                                @click="open = false"
                                @class([
                                    'flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-medium transition',
                                    'bg-neutral-800 text-white font-semibold' => $selectedRole === $role->value,
                                    'text-neutral-300 hover:bg-neutral-800 hover:text-white' => $selectedRole !== $role->value,
                                ])
                            >
                                <span>{{ $role->label() }}</span>
                                @if($selectedRole === $role->value)
                                    <i class="fa-solid fa-check text-xs text-sky-400"></i>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Search Box --}}
                <div class="relative w-full sm:w-80">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none text-neutral-400">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    </div>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        class="block h-11 w-full rounded-lg border border-neutral-700 bg-neutral-800 ps-10 pe-2.5 text-sm text-neutral-100 placeholder-neutral-400 transition focus:border-neutral-500 focus:outline-none"
                        placeholder="Vyhľadať používateľa..."
                    />
                    @if(!blank($search))
                        <button
                            type="button"
                            wire:click="$set('search', '')"
                            class="absolute inset-y-0 end-0 flex items-center pe-3 text-neutral-400 hover:text-white"
                        >
                            <i class="fa-solid fa-xmark text-xs"></i>
                        </button>
                    @endif
                </div>
            </div>

            {{-- User Table --}}
            <div class="relative overflow-x-auto">
                <table class="w-full text-sm text-left text-neutral-300">
                    <thead class="text-xs uppercase tracking-wider text-neutral-400 bg-neutral-900 border-b border-neutral-800">
                        <tr>
                            <x-table-cell-header-sortby :sortby="'name'">Meno</x-table-cell-header-sortby>
                            <x-table-cell-header-sortby :sortby="'email'">E-mail</x-table-cell-header-sortby>
                            <x-table-cell-header>Rola</x-table-cell-header>
                            <x-table-cell-header-sortby :sortby="'updated_at'">Posledná aktivita</x-table-cell-header-sortby>
                            <x-table-cell-header class="text-center">Akcie</x-table-cell-header>
                        </tr>
                    </thead>
                    <tbody wire:loading.class="opacity-50 transition-opacity duration-300" class="divide-y divide-neutral-800/80">
                        @forelse($users as $user)
                            <x-table-row>
                                <x-table-cell-header class="px-6 py-4 font-bold whitespace-nowrap text-white">
                                    <a href="{{ route('profile.show', ['user' => $user->id]) }}" class="hover:text-white transition-colors">
                                        {{ $user->lastname.' '.$user->name }}
                                    </a>
                                </x-table-cell-header>

                                <x-table-cell class="text-neutral-300">{{ $user->email }}</x-table-cell>

                                @php
                                    $isPending = is_null($user->pivot->approved_at);
                                    $roleName = $user->roles->first()?->name;
                                @endphp
                                <x-table-cell>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full border
                                        @if($isPending) bg-amber-500/10 text-amber-400 border-amber-500/30
                                        @elseif(! $user->is_active) bg-neutral-800 text-neutral-400 border-neutral-700
                                        @elseif($roleName === \App\Enums\Role::Admin->value) bg-rose-500/10 text-rose-400 border-rose-500/30
                                        @else bg-sky-500/10 text-sky-400 border-sky-500/30
                                        @endif">
                                        @if($isPending) Neoverený
                                        @elseif(! $user->is_active) Zablokovaný
                                        @else {{ \App\Enums\Role::tryFrom($roleName)?->label() ?? '—' }}
                                        @endif
                                    </span>
                                </x-table-cell>

                                <x-table-cell class="text-neutral-400">{{ $user->updated_at?->format('d.m.Y H:i') }}</x-table-cell>

                                <x-table-cell>
                                    <div class="flex items-center justify-center gap-2">
                                        @if($isPending)
                                            <button
                                                type="button"
                                                wire:click="accept({{$user->id}})"
                                                title="Schváliť"
                                                class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 hover:bg-emerald-500/30 hover:text-white transition focus:outline-none"
                                            >
                                                <i class="fa-solid fa-check text-sm"></i>
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="deny({{$user->id}})"
                                                title="Zamietnuť"
                                                class="flex h-9 w-9 items-center justify-center rounded-lg bg-rose-500/15 text-rose-400 border border-rose-500/30 hover:bg-rose-500/30 hover:text-white transition focus:outline-none"
                                            >
                                                <i class="fa-solid fa-xmark text-sm"></i>
                                            </button>
                                        @else
                                            <a
                                                href="{{ route('admin.users.edit', ['user' => $user->id]) }}"
                                                title="Upraviť používateľa"
                                                class="inline-flex items-center gap-1.5 rounded-lg bg-neutral-800 border border-neutral-700 px-3 py-1.5 text-xs font-semibold text-neutral-200 transition hover:bg-neutral-700 hover:text-white"
                                            >
                                                <i class="fa-solid fa-pen-to-square text-xs text-neutral-400"></i>
                                                Upraviť
                                            </a>
                                        @endif
                                    </div>
                                </x-table-cell>
                            </x-table-row>
                        @empty
                            <x-table-row>
                                <x-table-cell colspan="5" class="!p-0">
                                    <x-empty-state message="Nenašli sa žiadni používatelia." icon="fa-users-slash" class="rounded-none border-none bg-transparent py-12">
                                        @if(blank($search) && blank($selectedRole))
                                            Skúste zmeniť filtre alebo pridať nových používateľov.
                                        @else
                                            Skúste upraviť kritériá vyhľadávania alebo filtrovania.
                                        @endif
                                    </x-empty-state>
                                </x-table-cell>
                            </x-table-row>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <div class="p-4 bg-neutral-900 border-t border-neutral-800">
                    {{ $users->links('vendor.pagination.tailwind') }}
                </div>
            @endif
        </div>
    </div>
</div>
