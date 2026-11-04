<?php

namespace App\Http\Controllers\Calendar;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Services\CalendarService;
use App\Services\WeekService;
use App\Support\SlovakDate;
use App\Support\SlovakPlural;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The employee week view - the main screen.
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

    /** `/week/{date}` - any date inside the wanted week, realigned before use. */
    public function show(Request $request, Team $team, string $date): View
    {
        return $this->week($request, $team, $date);
    }

    private function week(Request $request, Team $team, ?string $date): View
    {
        $weekStart = $this->weeks->alignFromRequest($team, $date);
        $next = $this->weeks->next($weekStart);
        $week = $this->calendar->forWeek($team, $weekStart, $request->user());

        return view('calendar.index', array_merge(
            $week,
            [
                'title' => 'Týždeň '.SlovakDate::range($weekStart, $week['weekEnd']),
                'viewerId' => $request->user()->id,
                'canViewSummary' => $request->user()->hasPermissionInTeam('user.view-any', $team),
                'absenceCountLabel' => SlovakPlural::of($week['absences']->count(), 'absencia', 'absencie', 'absencií'),
                'weekPickerOptions' => [
                    'maxDate' => $this->weeks->end($this->weeks->lastOpenWeek($team))->toDateString(),
                    'weekUrl' => route('calendar.show', ['date' => '__DATE__']),
                    'currentWeekUrl' => route('calendar.index'),
                ],
                'previousWeek' => $this->weeks->previous($weekStart),

                // Null past the team's week_lookahead, so the view simply hides the arrow
                // rather than offering a link that silently lands back on this week.
                'nextWeek' => $this->weeks->clampForward($team, $next)->equalTo($next) ? $next : null,
                // Phones only (see .week-compact in app.css); chosen in /nastavenia.
                'compactWeek' => $request->user()->compact_week,
            ],
        ));
    }
}
