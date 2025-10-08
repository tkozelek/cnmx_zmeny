<?php

namespace App\Http\Controllers;

use App\Exports\WeeklyScheduleExport;
use App\Models\Week;
use App\Services\LocalizationService;
use App\Services\WeekDataService;
use App\Services\WeekService;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Writer\Exception;

class CalendarController extends Controller
{
    private int $num_of_weeks;

    private LocalizationService $localizationService;

    private WeekService $weekService;

    private WeekDataService $weekDataService;

    public function __construct(WeekService $weekService, LocalizationService $localizationService, WeekDataService $weekDataService)
    {
        $this->num_of_weeks = config('constants.calendar.generate_weeks');
        $this->weekService = $weekService;
        $this->localizationService = $localizationService;
        $this->weekDataService = $weekDataService;
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

        try {
            return Excel::download(new WeeklyScheduleExport($week), $date_from.'_'.$date_to.'.xlsx');
        } catch (Exception|\PhpOffice\PhpSpreadsheet\Exception $e) {
            abort(500);
        }
    }

    public function getData(Week $week): View
    {
        $days = $week->days()->with('users')->orderBy('date')->get();

        $data = $this->weekDataService->getDataForWeek($week, auth()->user());

        return view('calendar.index', array_merge([
            'days' => $days,
            'week' => $week,
            'title' => 'ZAPISOVANIE',
        ], $data));
    }
}
