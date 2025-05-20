<x-layout :title="$title">
    @if(isset($days) && count($days) != 0)
        <!-- TOP BUTTONS -->
        @if(auth()->user()->hasRole(config('constants.roles.admin')))
            @include('partials._adminbutton')
        @else
            @include('partials._nonadminbutton')
        @endif
        <x-date :week="$week" />
        <section class="md:container mx-auto">
            <!-- EXTRA -->
            <div class="text-center mb-2">
                @include('partials._toggle')
                @include('partials._extratext')
            </div>

            <!-- DAYS -->
            <div
                class="text-center grid-flow-row auto-rows-max grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7 2xl:grid-cols-7 gap-3">
                @foreach($days as $day)
                    <x-one-day :day="$day" :locked="$week->locked"/>
                @endforeach
            </div>

            <!-- USER COUNTS -->
            @if(isset($userCount) && count($userCount) != 0)
                @include('calendar.partials._usercount')
            @endif

            <!-- USER ABSENCES -->
            @if(isset($absences) && count($absences) != 0)
                @include('calendar.partials._absences')
            @endif
        </section>
    @else
        <p>no found</p>
    @endif

</x-layout>
