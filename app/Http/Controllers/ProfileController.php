<?php

namespace App\Http\Controllers;

use App\Charts\UserDaysTrendChart;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function index() {
        $this->getUserData(auth()->user());
        return view('profile.index', [
            'user' => auth()->user(),
            'chartuserdays' => new UserDaysTrendChart(),
        ]);
    }

    public function show(User $user) {

    }

    public function getUserData(User $user) {
        $results = DB::table('users')
            ->join('user_days', 'users.id', '=', 'user_days.id_user')
            ->join('days', 'user_days.id_day', '=', 'days.id')
            ->select(
                'users.id',
                DB::raw("CASE WHEN DAYOFWEEK(days.date) = 1 THEN 7 ELSE DAYOFWEEK(days.date) - 1 END as den"),
                DB::raw('COUNT(days.date) as day_count')
            )
            ->groupBy('users.id', 'den')
            ->orderBy('den')
            ->get();

        if (count($results) <= 7) {
            // TODO dokoncit doplnanie
        }

        dd($results);
    }
}
