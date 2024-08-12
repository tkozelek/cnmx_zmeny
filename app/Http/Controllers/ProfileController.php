<?php

namespace App\Http\Controllers;

use App\Charts\UserDaysTrendChart;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function index() {
        $user = auth()->user();
        $userChartArray = $this->getUserDayCountForEachDay($user);
        return view('profile.index', [
            'user' => $user,
            'chartuserdays' => new UserDaysTrendChart($userChartArray),
        ]);
    }

    public function show(User $user) {
        $userChartArray = $this->getUserDayCountForEachDay($user);
        return view('profile.index', [
            'user' => $user,
            'chartuserdays' => new UserDaysTrendChart($userChartArray),
        ]);
    }

    public function getUserDayCountForEachDay(User $user) {
        $results = DB::table('users')
            ->join('user_days', 'users.id', '=', 'user_days.id_user')
            ->join('days', 'user_days.id_day', '=', 'days.id')
            ->select(
                'users.id',
                DB::raw("CASE WHEN DAYOFWEEK(days.date) = 1 THEN 7 ELSE DAYOFWEEK(days.date) - 1 END as den"),
                DB::raw('COUNT(days.date) as day_count')
            )
            ->where('users.id', $user->id)
            ->groupBy('users.id', 'den')
            ->orderBy('den')
            ->get();
        $arr = [0,0,0,0,0,0,0];
        foreach ($results as $result) {
            $arr[$result->den-1] = $result->day_count;
        }
        return $arr;
    }
}
