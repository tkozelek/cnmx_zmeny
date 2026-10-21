<?php

namespace App\Filament\Platform\Widgets;

use App\Models\Assignment;
use App\Models\Team;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class PlatformStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $pending = DB::table('team_user')
            ->join('users', 'users.id', '=', 'team_user.user_id')
            ->whereNull('team_user.approved_at')
            ->whereNotNull('users.email_verified_at')
            ->where('users.is_active', true)
            ->count();

        return [
            Stat::make('Kiná', Team::count()),
            Stat::make('Aktívne účty', User::where('is_active', true)->where('is_super_admin', false)->count()),
            Stat::make('Čakajú na schválenie', $pending)->color($pending > 0 ? 'warning' : 'gray'),
            Stat::make('Zápisy tento týždeň', Assignment::query()
                ->whereBetween('date', [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()])
                ->count()),
        ];
    }
}
