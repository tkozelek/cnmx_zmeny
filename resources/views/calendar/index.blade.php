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
                    <x-table :headers="['Meno', 'Počet']" class="mx-auto max-w-md sm:mx-0">
                                @foreach($userCount as $user)
                                    <x-table-row>
                                        <x-table-cell class="font-medium text-slate-100">{{ $user }}</x-table-cell>
                                        <x-table-cell class="text-center">{{ $user->count }}</x-table-cell>
                                    </x-table-row>
                                @endforeach
                    </x-table>
                </div>
            @endif

            @if(isset($absences) && count($absences) != 0)
                <div class="mt-10 md:mt-12 mb-8">
                    <h3 class="text-lg sm:text-xl font-semibold text-slate-100 mb-4">Absencie v aktuálnom týždni</h3>
                    <x-table :headers="['Meno', 'Začiatok', 'Koniec', 'Vytvorené', 'Dôvod']">
                                @foreach($absences as $absence)
                                    <x-table-row>
                                        <x-table-cell class="font-medium text-slate-100">{{ $absence->user }}</x-table-cell>
                                        <x-table-cell class="whitespace-nowrap">{{ App\Helpers::getDateFromAttribute($absence->date_from, 'd.m') }}</x-table-cell>
                                        <x-table-cell class="whitespace-nowrap">{{ App\Helpers::getDateFromAttribute($absence->date_to, 'd.m') }}</x-table-cell>
                                        <x-table-cell class="whitespace-nowrap">{{ App\Helpers::getDateFromAttribute($absence->created_at, 'd.m H:i') }}</x-table-cell>
                                        <x-table-cell>{{ $absence->popis }}</x-table-cell>
                                    </x-table-row>
                                @endforeach
                    </x-table>
                </div>
            @endif
        </section>
    @else
        <p>no found</p>
    @endif

</x-layout>
