<?php

namespace App\Http\Controllers\Calendar;

use App\Exports\WeeklyScheduleExport;
use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Services\WeekService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * The week's plan as a spreadsheet.
 */
class ScheduleExportController extends Controller
{
    public function __construct(private readonly WeekService $weeks) {}

    public function __invoke(Request $request, Team $team, string $date): BinaryFileResponse
    {
        abort_unless($request->user()->hasPermissionInTeam('assignment.lock', $team), 403);

        $weekStart = $this->weeks->alignFromRequest($team, $date);
        $weekEnd = $this->weeks->end($weekStart);

        $filename = $weekStart->format('d_m_Y').'_'.$weekEnd->format('d_m_Y').'.xlsx';

        return Excel::download(new WeeklyScheduleExport($team, $weekStart), $filename);
    }
}
