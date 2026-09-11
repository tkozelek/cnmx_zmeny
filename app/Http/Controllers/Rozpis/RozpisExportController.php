<?php

namespace App\Http\Controllers\Rozpis;

use App\Exports\RozpisExport;
use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Services\RozpisService;
use App\Services\WeekService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * The finished rozpis as the printable spreadsheet the cinema pins to the wall.
 *
 * Separate from ScheduleExportController, which exports the signup stage - the two answer
 * different questions ("who is available" vs "who works what").
 */
class RozpisExportController extends Controller
{
    public function __construct(
        private readonly WeekService $weeks,
        private readonly RozpisService $rozpis,
    ) {}

    public function __invoke(Request $request, Team $team, string $date): BinaryFileResponse
    {
        abort_unless($request->user()->canBuildRozpis(), 403);

        $weekStart = $this->weeks->alignFromRequest($team, $date);
        $weekEnd = $this->weeks->end($weekStart);

        // Named the way the cinema already names these files, so a downloaded one drops straight
        // into the folder the old hand-made sheets live in.
        $filename = sprintf(
            'Rozpis zmien brigádnikov_%s_%s - %s.xlsx',
            $team->name,
            $weekStart->format('d.m.'),
            $weekEnd->format('d.m.'),
        );

        return Excel::download(new RozpisExport($team, $weekStart, $this->rozpis), $filename);
    }
}
