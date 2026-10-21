<?php

namespace App\Http\Controllers\Calendar;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Team;
use App\Models\WeekLock;
use App\Services\WeekService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Freezing a week. Locking creates (or re-locks) the row; unlocking deletes it, unless the week's
 * rozpis is published - the row then stays, unlocked, so the publication survives a late change.
 */
class WeekLockController extends Controller
{
    public function __construct(private readonly WeekService $weeks) {}

    public function store(Request $request, Team $team, string $date): RedirectResponse
    {
        $this->authorize('lock', Assignment::class);

        $weekStart = $this->weeks->alignFromRequest($team, $date);

        $lock = WeekLock::firstOrCreate(
            ['week_start' => $weekStart->toDateString()],
            ['locked_by' => $request->user()->id],
        );

        if (! $lock->is_locked) {
            $lock->update(['is_locked' => true, 'locked_by' => $request->user()->id]);
        }

        return $this->backToWeek($weekStart->toDateString(), 'Týždeň zamknutý.', 'fa fa-lock');
    }

    public function destroy(Team $team, string $date): RedirectResponse
    {
        $this->authorize('lock', Assignment::class);

        $weekStart = $this->weeks->alignFromRequest($team, $date);

        // Through the model, not a mass delete, so the change reaches the activity log.
        WeekLock::where('week_start', $weekStart->toDateString())->first()?->unlock();

        return $this->backToWeek($weekStart->toDateString(), 'Týždeň odomknutý.', 'fa fa-lock-open');
    }

    private function backToWeek(string $weekStart, string $message, string $icon): RedirectResponse
    {
        return redirect()
            ->route('calendar.show', ['date' => $weekStart])
            ->with(['message' => $message, 'icon' => $icon]);
    }
}
