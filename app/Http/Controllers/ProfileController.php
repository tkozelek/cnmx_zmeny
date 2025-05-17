<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $userChartArray = $this->getUserDayCountForEachDay($user);

        return view('profile.index', [
            'user' => $user,
            'daysCount' => $this->getCountOfDaysInTime($user),
            'arr' => $userChartArray,
        ]);
    }

    public function show(User $user, Request $request)
    {
        $date = null;
        if ($request->has('date')) {
            $date = $this->transformDate($request->input('date'));
        }

        $userChartArray = $this->getUserDayCountForEachDay($user, $date);

        return view('profile.index', [
            'user' => $user,
            'daysCount' => $this->getCountOfDaysInTime($user, $date),
            'arr' => $userChartArray,
        ]);
    }

    public function getUserDayCountForEachDay(User $user, $date = null)
    {
        $results = DB::table('users')
            ->join('user_days', 'users.id', '=', 'user_days.id_user')
            ->join('days', 'user_days.id_day', '=', 'days.id')
            ->select(
                'users.id',
                DB::raw('CASE WHEN DAYOFWEEK(days.date) = 1 THEN 7 ELSE DAYOFWEEK(days.date) - 1 END as den'),
                DB::raw('COUNT(days.date) as day_count')
            )
            ->where('users.id', $user->id)

            ->groupBy('users.id', 'den')
            ->orderBy('den');

        if ($date) {
            $results = $results->whereDate('date', '>=', $date->toDateString());
        }
        $results = $results->get();

        $arr = [0, 0, 0, 0, 0, 0, 0];
        foreach ($results as $result) {
            $arr[$result->den - 1] = $result->day_count;
        }

        return $arr;
    }

    public function getCountOfDaysInTime(User $user, $date = null)
    {
        if ($date) {
            return $user->days()->whereDate('date', '>=', $date->toDateString())->count();
        }

        return $user->days()->count();
    }

    private function transformDate($dateString)
    {
        return match ($dateString) {
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
            default => null,
        };
    }
}
