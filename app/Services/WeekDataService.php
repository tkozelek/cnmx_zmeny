<?php

namespace App\Services;

use App\Models\File;
use App\Models\Holiday;
use App\Models\User;
use App\Models\Week;
use DB;

class WeekDataService
{
    public function getDataForWeek(Week $week, User $user): array
    {
        $data = [
            'userCount' => null,
            'absences' => null,
            'files' => File::forWeek($week)->visibleTo($user)->get(),
            'file' => null,
        ];

        if ($user->hasRole(config('constants.roles.admin'))) {
            $data['userCount'] = $this->getUserDayCounts($week);
            $data['absences'] = Holiday::forWeek($week)->get();
        }

        return $data;
    }

    protected function getUserDayCounts(Week $week)
    {
        return User::select(
            'users.id as id_user',
            'users.lastname',
            'users.name',
            DB::raw('COUNT(user_days.id_user) as count')
        )
            ->join('user_days', 'users.id', '=', 'user_days.id_user')
            ->join('days', 'user_days.id_day', '=', 'days.id')
            ->where('days.id_week', $week->id)
            ->groupBy('users.id', 'users.lastname', 'users.name')
            ->orderBy('count', 'desc')
            ->get();
    }
}
