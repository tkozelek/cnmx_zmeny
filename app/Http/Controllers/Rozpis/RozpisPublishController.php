<?php

namespace App\Http\Controllers\Rozpis;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\WeekLock;
use App\Services\WeekService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Releasing a finished rozpis to the employees.
 *
 * The third and last phase of a week: signup, then the manager's build, then publication. Until
 * this runs the plan is a draft only the manager can see, so half-finished days never reach the
 * people who have to turn up for them.
 *
 * Withdrawing is a plain undo - the plan goes back to draft and the read-only page stops
 * answering for everyone but the manager. Nothing is deleted, so republishing is one click.
 */
class RozpisPublishController extends Controller
{
    public function __construct(private readonly WeekService $weeks) {}

    public function store(Request $request, Team $team, string $date): RedirectResponse
    {
        $lock = $this->lockFor($request, $team, $date);

        $lock->update([
            'rozpis_published_at' => now(),
            'published_by' => $request->user()->id,
        ]);

        return $this->back(
            $lock->week_start->toDateString(),
            'Rozpis zverejnený - zamestnanci ho už vidia.',
            'fa fa-bullhorn',
        );
    }

    public function destroy(Request $request, Team $team, string $date): RedirectResponse
    {
        $lock = $this->lockFor($request, $team, $date);

        $lock->update(['rozpis_published_at' => null, 'published_by' => null]);

        return $this->back(
            $lock->week_start->toDateString(),
            'Rozpis stiahnutý zo zverejnenia.',
            'fa fa-eye-slash',
        );
    }

    /**
     * The week's lock row, which is also what publication hangs off.
     *
     * A missing row means the week was never locked, and an unlocked week has no rozpis to
     * publish - 404 rather than silently creating a lock as a side effect of publishing.
     */
    private function lockFor(Request $request, Team $team, string $date): WeekLock
    {
        abort_unless($request->user()->canBuildRozpis(), 403);

        $weekStart = $this->weeks->alignFromRequest($team, $date);

        return WeekLock::forWeek($team->getKey(), $weekStart) ?? abort(404);
    }

    private function back(string $weekStart, string $message, string $icon): RedirectResponse
    {
        return redirect()
            ->route('rozpis.show', ['date' => $weekStart])
            ->with(['message' => $message, 'icon' => $icon]);
    }
}
