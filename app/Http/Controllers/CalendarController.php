<?php

namespace App\Http\Controllers;

use App\Exports\WeeklyScheduleExport;
use App\Models\Day;
use App\Models\File;
use App\Models\Holiday;
use App\Models\User;
use App\Models\Week;
use App\Services\LocalizationService;
use App\Services\WeekService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class CalendarController extends Controller
{
    private int $num_of_weeks;

    private LocalizationService $localizationService;

    private WeekService $weekService;

    public function __construct(WeekService $weekService, LocalizationService $localizationService)
    {
        $this->num_of_weeks = config('constants.calendar.generate_weeks');
        $this->weekService = $weekService;
        $this->localizationService = $localizationService;
    }

    public function index()
    {
        return $this->getData($this->weekService->getActiveWeek());
    }

    public function show(Week $week)
    {
        return $this->getData($week);
    }

    public function lock(Week $week)
    {
        $this->weekService->checkAndGenerateWeeks($this->num_of_weeks);

        $week->locked = ! $week->locked;

        $week->save();

        $week->locked ? $msg = 'Týždeň zamknutý.' : $msg = 'Týždeň odomknutý.';
        $week->locked ? $icon = 'fa fa-lock' : $icon = 'fa fa-lock-open';

        return redirect()->route('calendar.show', ['week' => $week->id])->with(['message' => $msg, 'icon' => $icon]);
    }

    public function export(Week $week)
    {
        $date_from = $week->date_from->format('d_m_Y');
        $date_to = $week->date_to->format('d_m_Y');

        return Excel::download(new WeeklyScheduleExport($week), $date_from.'_'.$date_to.'.xlsx');
    }

    public function addUser(Request $request)
    {
        $user = auth()->user();
        $dayId = $request->input('day');

        if ($user->id_role == config('constants.roles.zablokovany')) {
            return response()->json(['message' => 'Uživateľ je zablokovaný', 'error' => 10], 400);
        }

        $day = Day::with('week', 'users')->find($dayId);

        if (! $day) {
            return response()->json(['message' => 'Deň nebol nájdený'], 404);
        }

        $week = $day->week;

        if ($week->locked) {
            return response()->json(['message' => 'Týždeň je uzamknutý', 'error' => 10], 400);
        }

        $existingUser = $day->users->contains($user->id);

        if ($existingUser) {
            $day->users()->detach($user->id);
            $message = 'User removed from day';
            $status = 2;
        } else {
            $popis = $request->input('popis');
            $day->users()->attach($user->id, ['popis' => $popis]);
            $message = 'User added to day';
            $status = 1;
        }

        return response()->json(['message' => $message, 'users' => $day->users()->get(), 'status' => $status]);
    }

    public function destroy(Day $day, User $user)
    {
        if ($user->days()->detach($day->id)) {
            return back()->with(['message' => 'Použivateľ bol vymazaný z daného dňa.']);
        } else {
            return back()->with(['error' => 'Chyba, použivateľ nebol vymazaný.']);
        }
    }

    /**
     * @param  $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Foundation\Application
     */
    public function getData(Week $week): View
    {
        $days = $week->days()->with('users')->orderBy('date')->get();

        $userDayCount = null;
        $absences = null;
        $files = null;
        if (auth()->user()->hasRole(config('constants.roles.admin'))) {
            $userDayCount = User::select('users.id as id_user', 'users.lastname as lastname', 'users.name as name', DB::raw('COUNT(user_days.id_user) as count'), 'days.id_week')
                ->leftJoin('user_days', 'users.id', '=', 'user_days.id_user')
                ->leftJoin('days', 'user_days.id_day', '=', 'days.id')
                ->where('days.id_week', $week->id)
                ->groupBy('users.id', 'users.lastname', 'users.name', 'days.id_week')
                ->get();
            $absences = Holiday::where('date_to', '>=', $week->date_from)
                ->where('date_canceled', null)
                ->get();
            $files = File::where('id_week', $week->id)->get();
        } else {
            $files = File::where('id_week', $week->id)
                ->where('is_shown', 1)
                ->get();
        }

        return view('calendar.index', [
            'days' => $days,
            'week' => $week,
            'userCount' => $userDayCount ?: null,
            'absences' => $absences ?: null,
            'files' => $files ?: null,
            'title' => 'ZAPISOVANIE',
        ]);
    }
}
