<?php

namespace App\Http\Controllers\Absence;

use App\Http\Controllers\Controller;
use App\Http\Requests\Absence\StoreAbsenceRequest;
use App\Models\Absence;
use App\Models\Team;
use App\Services\AbsenceService;
use App\Support\SlovakPlural;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Marking yourself unavailable. Replaces the legacy HolidayController.
 */
class AbsenceController extends Controller
{
    public function __construct(private readonly AbsenceService $absences) {}

    public function index(Request $request, Team $team): View
    {
        // Managers are exempt from the deadline (StoreAbsenceRequest::after). For everyone else the
        // picker opens on the first day they may still report, instead of a today that always fails.
        $isManager = $request->user()->hasPermissionInTeam('absence.manage', $team);
        $deadlineDays = $isManager ? 0 : $team->absenceDeadlineDays();
        $firstAllowedDay = now()->addDays($deadlineDays)->toDateString();

        return view('holiday.index', [
            'firstAllowedDay' => $firstAllowedDay,
            'minDate' => $isManager ? null : $firstAllowedDay,
            'deadlineLabel' => $deadlineDays > 0 ? SlovakPlural::of($deadlineDays, 'deň', 'dni', 'dní') : null,
            // Nothing reported yet: an empty state instead of a search, filters and headers over zero rows.
            'hasOwnAbsences' => $request->user()->absences()->exists(),
            'openDialog' => $request->boolean('nova'),
        ]);
    }

    public function store(StoreAbsenceRequest $request): RedirectResponse
    {
        $request->user()->absences()->create($request->safe()->except('open_ended'));

        return back()->with(['message' => 'Absencia nahlásená.']);
    }

    public function destroy(Absence $absence): RedirectResponse
    {
        $this->authorize('delete', $absence);

        $absence->delete();

        return back()->with(['message' => 'Absencia vymazaná.']);
    }

    /** End an absence early rather than deleting its history. */
    public function end(Absence $absence): RedirectResponse
    {
        $this->authorize('end', $absence);

        $this->absences->end($absence);

        return back()->with(['message' => 'Absencia deaktivovaná.']);
    }
}
