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
                <div class="flex mt-10 mb-5">
                    <div class="">
                        <table class="table-auto border border-gray-800 bg-slate-900 text-gray-200">
                            <thead>
                            <tr class="">
                                <x-table-cell-header class="!px-2 !py-1">Meno</x-table-cell-header>
                                <x-table-cell-header class="!px-2 !py-1">Počet</x-table-cell-header>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($userCount as $user)
                                <x-table-row>
                                    <x-table-cell-header class="!px-2 !py-1">{{ $user }}</x-table-cell-header>
                                    <x-table-cell class="!px-2 !py-1">{{ $user->count }}</x-table-cell>
                                </x-table-row>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if(isset($absences) && count($absences) != 0)
                <div class="flex">
                    <div class="overflow-x-auto">
                        <table class="table-auto border border-gray-800 bg-slate-900 text-gray-200">
                            <thead class="">
                            <tr class="">
                                <x-table-cell-header class="!px-2 !py-1">Meno</x-table-cell-header>
                                <x-table-cell-header class="!px-2 !py-1">Začiatok</x-table-cell-header>
                                <x-table-cell-header class="!px-2 !py-1">Koniec</x-table-cell-header>
                                <x-table-cell-header class="!px-2 !py-1">Dôvod</x-table-cell-header>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($absences as $absence)
                                <x-table-row>
                                    <x-table-cell-header class="!px-2 !py-1">{{ $absence->user }}</x-table-cell-header>
                                    <x-table-cell class="!px-2 !py-1">{{ App\Helpers::getDateFromAttribute($absence->date_from, 'd.m.Y') }}</x-table-cell>
                                    <x-table-cell class="!px-2 !py-1">{{ App\Helpers::getDateFromAttribute($absence->date_to, 'd.m.Y') }}</x-table-cell>
                                    <x-table-cell class="!px-2 !py-1">{{ $absence->popis }}</x-table-cell>
                                </x-table-row>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </section>
    @else
        <p>no found</p>
    @endif

</x-layout>
