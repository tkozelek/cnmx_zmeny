<?php

namespace App\Http\Controllers\Calendar;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Services\CalendarService;
use App\Services\WeekService;
use App\Support\SlovakDate;
use App\Support\SlovakPlural;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
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
        return $this->week($request, $team, $this->weeks->alignFromRequest($team, null));
    }

    /** `/week/{date}` - any date inside the wanted week, realigned before use. */
    public function show(Request $request, Team $team, string $date): View|RedirectResponse
    {
        $weekStart = $this->weeks->alignFromRequest($team, $date);
        $requested = rescue(fn (): CarbonImmutable => CarbonImmutable::parse($date)->startOfDay(), report: false);

        // A date past the lookahead (or no date at all) shows another week - give it that week's own URL.
        if ($requested === null || $requested->gt($this->weeks->end($weekStart))) {
            return redirect()->route('calendar.show', ['date' => $weekStart->toDateString()]);
        }

        return $this->week($request, $team, $weekStart);
    }

    private function week(Request $request, Team $team, CarbonImmutable $weekStart): View
    {
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
