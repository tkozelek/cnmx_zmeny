<x-layout
    :title="$title"
    description="Prehľad odpracovaných zmien a absencií."
>
    <div class="w-full min-w-0 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8 space-y-3.5 sm:space-y-6"
         x-data="{
             tab: 'stats',
             period: @js($activePeriod),
             periods: @js($periods),
             get currentPeriod() { return this.periods[this.period] || this.periods['all']; },
             setPeriod(p) {
                 this.period = p;
                 this.$nextTick(() => {
                     if (typeof window.renderProfileChart === 'function') {
                         window.renderProfileChart(this.currentPeriod.counts);
                     }
                 });
             }
         }"
         x-init="window.location.href.indexOf('page') > -1 ? tab = 'absences' : tab = 'stats'; $nextTick(() => { if (typeof window.renderProfileChart === 'function') window.renderProfileChart(currentPeriod.counts); })">

        <x-page-header
            icon="fa-user"
            :title="$title"
        >
            <x-slot:breadcrumb>
                <nav class="flex items-center gap-1 sm:gap-1.5 text-xs font-medium text-neutral-400" aria-label="Omrvinková navigácia">
                    <a href="{{ route('calendar.index') }}" class="-mx-1 inline-flex min-h-10 items-center px-1 hover:text-neutral-200 transition">Týždne</a>
                    <i class="fa-solid fa-chevron-right text-[9px] text-neutral-400" aria-hidden="true"></i>
                    @if($canViewUsers)
                        <a href="{{ route('admin.users.index') }}" class="-mx-1 inline-flex min-h-10 items-center px-1 hover:text-neutral-200 transition">Používatelia</a>
                        <i class="fa-solid fa-chevron-right text-[9px] text-neutral-400" aria-hidden="true"></i>
                    @endif
                    <span class="text-neutral-300" aria-current="page">Profil</span>
                </nav>
            </x-slot:breadcrumb>

            <a href="{{ $backUrl }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 text-xs font-semibold text-neutral-400 bg-neutral-900 border border-neutral-800 rounded-lg hover:bg-neutral-800 hover:text-neutral-200 transition shadow-sm">
                <i class="fa-solid fa-arrow-left text-[11px]" aria-hidden="true"></i>
                <span>Späť</span>
            </a>
        </x-page-header>

        {{-- User Identity Card --}}
        @include('profile.partials._user_card')

        {{-- KPI Metrics --}}
        @include('profile.partials._kpis')

        {{-- Main Card with Tabs --}}
        <div class="rounded-lg border border-neutral-800 bg-neutral-900 shadow-sm overflow-hidden">
            {{-- Tabs Header Bar --}}
            @include('profile.partials._tab_header')

            {{-- Tab 1: Stats Section --}}
            @include('profile.partials._stats_tab')

            {{-- Tab 2: Absences Section --}}
            @include('profile.partials._absences_tab')
        </div>
    </div>
</x-layout>
