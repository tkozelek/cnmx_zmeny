<x-layout :title="$title">
    @if(isset($days) && count($days) != 0)
        @if(auth()->user()->hasRole(config('constants.roles.admin')))
            @include('partials._adminbutton')
        @else
            @include('partials._nonadminbutton')
        @endif
        <x-date :week="$week" />
        <section class="md:container mx-auto">
            <div class="text-center mb-2">
                @include('partials._toggle')
                @include('partials._extratext')
            </div>
            <div
                class="text-center grid-flow-row auto-rows-max grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7 2xl:grid-cols-7 gap-3">
                @foreach($days as $day)
                    <x-one-day :day="$day" :locked="$week->locked"/>
                @endforeach
            </div>
            @if(isset($userCount) && count($userCount) != 0)
                <div class="mt-10 md:mt-12 mb-8">
                    <h3 class="text-lg sm:text-xl font-semibold text-slate-100 mb-4">Prehľad počtu zapísaných</h3>
                    <div class="bg-slate-800 shadow-xl rounded-lg overflow-hidden max-w-md mx-auto sm:mx-0">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-slate-300">
                                <thead class="text-xs text-slate-200 uppercase bg-slate-700/60">
                                <tr>
                                    <th scope="col" class="px-4 py-3 font-medium">Meno</th>
                                    <th scope="col" class="px-4 py-3 font-medium text-center">Počet</th>
                                </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-700">
                                @foreach($userCount as $user)
                                    <tr class="hover:bg-slate-700/40 transition-colors duration-150">
                                        <td class="px-4 py-3 font-medium text-slate-100">{{ $user }}</td>
                                        <td class="px-4 py-3 text-center">{{ $user->count }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            @if(isset($absences) && count($absences) != 0)
                <div class="mt-10 md:mt-12 mb-8">
                    <h3 class="text-lg sm:text-xl font-semibold text-slate-100 mb-4">Absencie v aktuálnom týždni</h3>
                    <div class="bg-slate-800 shadow-xl rounded-lg overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-slate-300">
                                <thead class="text-xs text-slate-200 uppercase bg-slate-700/60">
                                <tr>
                                    <th scope="col" class="px-6 py-3 font-medium">Meno</th>
                                    <th scope="col" class="px-6 py-3 font-medium">Začiatok</th>
                                    <th scope="col" class="px-6 py-3 font-medium">Koniec</th>
                                    <th scope="col" class="px-6 py-3 font-medium">Vytvorené</th>
                                    <th scope="col" class="px-6 py-3 font-medium">Dôvod</th>
                                </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-700">
                                @foreach($absences as $absence)
                                    <tr class="hover:bg-slate-700/40 transition-colors duration-150">
                                        <td class="px-6 py-3 font-medium text-slate-100">{{ $absence->user }}</td>
                                        <td class="px-6 py-3 whitespace-nowrap">{{ App\Helpers::getDateFromAttribute($absence->date_from, 'd.m') }}</td>
                                        <td class="px-6 py-3 whitespace-nowrap">{{ App\Helpers::getDateFromAttribute($absence->date_to, 'd.m') }}</td>
                                        <td class="px-6 py-3 whitespace-nowrap">{{ App\Helpers::getDateFromAttribute($absence->created_at, 'd.m H:i') }}</td>
                                        <td class="px-6 py-3">{{ $absence->popis }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </section>
    @else
        <p>no found</p>
    @endif

</x-layout>
