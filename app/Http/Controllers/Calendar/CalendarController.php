<?php

namespace App\Http\Controllers\Calendar;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Services\CalendarService;
use App\Services\WeekService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The employee week view — the main screen.
 */
class CalendarController extends Controller
{
    public function __construct(
        private readonly WeekService $weeks,
        private readonly CalendarService $calendar,
    ) {}

    public function index(Request $request, Team $team): View
    {
        return $this->week($request, $team, null);
    }

    /** `/week/{date}` — any date inside the wanted week, realigned before use. */
    public function show(Request $request, Team $team, string $date): View
    {
        return $this->week($request, $team, $date);
    }

    private function week(Request $request, Team $team, ?string $date): View
    {
        $weekStart = $this->weeks->alignFromRequest($team, $date);
        $next = $this->weeks->next($weekStart);

        return view('calendar.index', array_merge(
            $this->calendar->forWeek($team, $weekStart, $request->user()),
            [
                'title' => 'ZAPISOVANIE',
                'previousWeek' => $this->weeks->previous($weekStart),

                // Null past the team's week_lookahead, so the view simply hides the arrow
                // rather than offering a link that silently lands back on this week.
                'nextWeek' => $this->weeks->clampForward($team, $next)->equalTo($next) ? $next : null,
            ],
        ));
    }
}
