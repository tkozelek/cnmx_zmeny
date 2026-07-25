<x-layout>
    <div class="xl:container mx-auto mt-5 mb-5" x-data="{ tab: 'stats' }" x-init="window.location.href.indexOf('page') > -1 ? tab = 'absences' : tab = 'stats'">
        <div class="grid grid-cols-1 md:grid-cols-10 lg:grid-cols-10 gap-6">

            <div class="col-span-1 md:col-span-3 lg:col-span-3 p-6 bg-slate-900 border border-slate-800 rounded-xl shadow-lg">
                <div class="flex flex-col items-center md:items-start">
                    <h2 class="text-2xl font-bold tracking-wider text-white">Používateľ</h2>
                    <hr class="w-full border-t border-slate-700 my-4">
                </div>
                <div class="space-y-4 text-sm">
                    <x-user-info text="Meno">{{ $user->name }}</x-user-info>
                    <x-user-info text="Priezvisko">{{ $user->lastname }}</x-user-info>
                    <x-user-info text="E-mail">{{ $user->email }}</x-user-info>
                    <x-user-info text="Rola">{{ \App\Enums\Role::tryFrom($user->getRoleNames()->first() ?? '')?->label() ?? 'Brigádnik' }}</x-user-info>
                    <x-user-info text="Vytvorený">{{ $user->created_at->format('d.m.Y H:i') }}</x-user-info>
                    <x-user-info text="Upravený">{{ $user->updated_at->format('d.m.Y H:i') }}</x-user-info>
                    <x-user-info text="Posledné prihlásenie">{{ $user->last_login_at ? $user->last_login_at->format('d.m.Y H:i') : '-' }}</x-user-info>
                </div>
            </div>

            <div class="col-span-1 md:col-span-7 lg:col-span-7">
                <div class="mb-4 border-b border-slate-700">
                    <nav class="flex -mb-px space-x-6">
                        <button @click="tab = 'stats'" :class="{ 'border-indigo-500 text-indigo-400': tab === 'stats', 'border-transparent text-slate-400 hover:text-white hover:border-slate-500': tab !== 'stats' }" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-lg focus:outline-none transition-colors">
                            Štatistiky
                        </button>
                        <button @click="tab = 'absences'" :class="{ 'border-indigo-500 text-indigo-400': tab === 'absences', 'border-transparent text-slate-400 hover:text-white hover:border-slate-500': tab !== 'absences' }" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-lg focus:outline-none transition-colors">
                            Absencie
                        </button>
                    </nav>
                </div>

                <div class="bg-slate-900 border border-slate-800 rounded-xl shadow-lg p-6">
                    <section x-show="tab === 'stats'" x-transition>
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4">
                            <div>
                                <h3 class="text-2xl font-semibold text-white">Frekvencia zápisov</h3>
                                <p class="text-lg text-slate-400">Počet zapísaných dní v období: <span class="font-bold text-white">{{ $daysCount }}</span></p>
                            </div>
                            <form id="dateForm" action="{{ route('profile.show', ['user' => $user->id]) }}" method="get" class="mt-4 sm:mt-0">
                                <select name="date" id="date" class="border text-sm rounded-lg block w-full p-2.5 bg-slate-800 border-slate-700 placeholder-slate-400 text-white focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="all" {{ request()->get('date') == 'all' ? 'selected' : '' }}>Celá doba</option>
                                    <option value="month" {{ request()->get('date') == 'month' ? 'selected' : '' }}>Tento mesiac</option>
                                    <option value="year" {{ request()->get('date') == 'year' ? 'selected' : '' }}>Tento rok</option>
                                </select>
                            </form>
                        </div>
                        <div style="width: 90%; margin: auto;">
                            <canvas id="barChart"></canvas>
                        </div>
                    </section>

                    <section x-show="tab === 'absences'" x-transition>
                        <h3 class="text-2xl font-semibold text-white mb-4">Aktívne absencie</h3>
                        <div class="space-y-3">
                            @forelse ($activeAbsences as $absence)
                                <div class="bg-slate-800 p-4 rounded-lg flex flex-col sm:flex-row justify-between sm:items-center gap-3">
                                    <div class="flex-1">
                                        <p class="font-bold text-white">{{ $absence->reason ?: 'Bez dôvodu' }}</p>
                                        <p class="text-sm text-slate-400">
                                            {{ $absence->date_from->format('d.m.Y') }} - {{ $absence->date_to->format('d.m.Y') }}
                                        </p>
                                    </div>
                                </div>
                            @empty
                                <p class="text-slate-400">Žiadne aktívne absencie.</p>
                            @endforelse
                        </div>

                        <hr class="w-full border-t border-slate-700 my-6">

                        <h3 class="text-2xl font-semibold text-white mb-4">História absencií</h3>
                        <div class="space-y-3">
                            @forelse ($inactiveAbsences as $absence)
                                <div class="bg-slate-800 p-4 rounded-lg flex flex-col sm:flex-row justify-between sm:items-center gap-3">
                                    <div class="flex-1">
                                        <p class="font-bold text-white">{{ $absence->reason ?: 'Bez dôvodu' }}</p>
                                        <p class="text-sm text-slate-400">
                                            {{ $absence->date_from->format('d.m.Y') }} - {{ $absence->date_to->format('d.m.Y') }}
                                        </p>
                                    </div>
                                    <span class="text-xs font-medium px-2.5 py-0.5 rounded-full bg-slate-700 text-slate-300 self-start sm:self-center">Vypršaná</span>
                                </div>
                            @empty
                                <p class="text-slate-400">Žiadne predošlé absencie.</p>
                            @endforelse
                            @if ($inactiveAbsences->hasPages())
                                    <div class="p-6 border-t border-slate-700 bg-slate-800">
                                        {{ $inactiveAbsences->links('vendor.pagination.tailwind') }}
                                    </div>
                            @endif
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>

    <script>
        var chartData = @json($arr);

        document.getElementById('date').addEventListener('change', function() {
            document.getElementById('dateForm').submit();
        });

    </script>
</x-layout>
