<?php

namespace App\Http\Controllers\Absence;

use App\Http\Controllers\Controller;
use App\Http\Requests\Absence\StoreAbsenceRequest;
use App\Models\Absence;
use App\Services\AbsenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Marking yourself unavailable. Replaces the legacy HolidayController.
 */
class AbsenceController extends Controller
{
    public function __construct(private readonly AbsenceService $absences) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $isAdmin = $user->hasRole('admin');

        return view('holiday.index', [
            'absences' => $user->absences()->orderByDesc('date_to')->get(),

            // Admins additionally see everyone's, which is what the policy gates.
            'active' => $isAdmin ? $this->absences->activeFor() : null,
            'inactive' => $isAdmin ? $this->absences->pastFor() : null,
        ]);
    }

    public function store(StoreAbsenceRequest $request): RedirectResponse
    {
        $request->user()->absences()->create($request->safe()->except('open_ended'));

        return back()->with(['message' => 'Absencia vytvorená.']);
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

        return back()->with(['message' => 'Absencia ukončená.']);
    }
}
