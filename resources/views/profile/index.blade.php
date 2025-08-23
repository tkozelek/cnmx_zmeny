<x-layout>
    <div class="xl:container mx-auto mt-5" x-data="{ tab: 'stats' }">
        <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-5 gap-6">

            <div class="col-span-1 p-6 bg-gray-800 rounded-xl shadow-lg">
                <div class="flex flex-col items-center md:items-start">
                    <h2 class="text-2xl font-bold tracking-wider text-white">Používateľ</h2>
                    <hr class="w-full border-t border-gray-600 my-4">
                </div>
                <div class="space-y-4 text-sm">
                    <x-user-info text="Meno">{{ $user->name }}</x-user-info>
                    <x-user-info text="Priezvisko">{{ $user->lastname }}</x-user-info>
                    <x-user-info text="E-mail">{{ $user->email }}</x-user-info>
                    <x-user-info text="Rola">{{ $user->role->text }}</x-user-info>
                    <x-user-info text="Vytvorený">{{ $user->created_at }}</x-user-info>
                    <x-user-info text="Upravený">{{ $user->updated_at }}</x-user-info>
                    <x-user-info text="Posledné prihlásenie">{{ isset($user->last_login_at) ? $user->getDate($user->last_login_at) : '-' }}</x-user-info>
                </div>
            </div>

            <div class="col-span-1 md:col-span-3 lg:col-span-4">
                <div class="mb-4 border-b border-gray-600">
                    <nav class="flex -mb-px space-x-6">
                        <button @click="tab = 'stats'" :class="{ 'border-blue-500 text-blue-500': tab === 'stats', 'border-transparent text-gray-400 hover:text-white hover:border-gray-500': tab !== 'stats' }" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-lg focus:outline-none">
                            Štatistiky
                        </button>
                        <button @click="tab = 'absences'" :class="{ 'border-blue-500 text-blue-500': tab === 'absences', 'border-transparent text-gray-400 hover:text-white hover:border-gray-500': tab !== 'absences' }" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-lg focus:outline-none">
                            Absencie
                        </button>
                    </nav>
                </div>

                <div class="bg-gray-800 rounded-xl shadow-lg p-6">
                    <section x-show="tab === 'stats'">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4">
                            <div>
                                <h3 class="text-2xl font-semibold text-white">Frekvencia zápisov</h3>
                                <p class="text-lg text-gray-400">Počet zapísaných dní v období: <span class="font-bold text-white">{{ $daysCount }}</span></p>
                            </div>
                            <form id="dateForm" action="{{ route('profile.show', ['user' => $user->id]) }}" method="get" class="mt-4 sm:mt-0">
                                <select name="date" id="date" class="border text-sm rounded-lg block w-full p-2.5 bg-gray-700 border-gray-600 placeholder-gray-400 text-white focus:ring-blue-500 focus:border-blue-500">
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

                    <section x-show="tab === 'absences'">
                        <h3 class="text-2xl font-semibold text-white mb-4">Aktívne absencie</h3>
                        <div class="space-y-3">
                            @forelse ($activeAbsences as $absence)
                                <div class="bg-gray-700 p-4 rounded-lg flex flex-col sm:flex-row justify-between sm:items-center gap-3">
                                    <div class="flex-1">
                                        <p class="font-bold text-white">{{ $absence->popis }}</p>
                                        <p class="text-sm text-gray-400">
                                            {{ $absence->date_from->format('d.m.Y') }} - {{ $absence->date_to->format('d.m.Y') }}
                                        </p>
                                    </div>
                                </div>
                            @empty
                                <p class="text-gray-400">Žiadne aktívne absencie.</p>
                            @endforelse
                        </div>

                        <hr class="w-full border-t border-gray-600 my-6">

                        <h3 class="text-2xl font-semibold text-white mb-4">História absencií</h3>
                        <div class="space-y-3">
                            @forelse ($inactiveAbsences as $absence)
                                <div class="bg-gray-700 p-4 rounded-lg flex flex-col sm:flex-row justify-between sm:items-center gap-3">
                                    <div class="flex-1">
                                        <p class="font-bold text-white">{{ $absence->popis }}</p>
                                        <p class="text-sm text-gray-400">
                                            {{ $absence->date_from->format('d.m.Y') }} - {{ $absence->date_to->format('d.m.Y') }}
                                        </p>
                                    </div>
                                    <span class="text-xs font-medium mr-2 px-2.5 py-0.5 rounded-full bg-gray-600 text-gray-300 self-start sm:self-center">{{ $absence->date_canceled }}</span>
                                </div>
                            @empty
                                <p class="text-gray-400">Žiadne predošlé absencie.</p>
                            @endforelse
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Pass chart data from your controller
        var chartData = @json($arr);

        // Submit form on select change
        document.getElementById('date').addEventListener('change', function() {
            document.getElementById('dateForm').submit();
        });
    </script>
</x-layout>
