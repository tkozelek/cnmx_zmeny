<?php

namespace App\Http\Controllers\Rozpis;

use App\Http\Controllers\Controller;
use App\Models\PositionSlot;
use App\Models\Team;
use App\Models\WeekLock;
use App\Services\RozpisService;
use App\Services\WeekService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The manager's step: turning a locked week's signups into an actual shift plan.
 *
 * Gated the opposite way to the calendar — the builder opens only *once the week is locked*,
 * because locking is what closes self-signup. Before that there is nothing stable to assign.
 */
class RozpisController extends Controller
{
    public function __construct(
        private readonly WeekService $weeks,
        private readonly RozpisService $rozpis,
    ) {}

    public function show(Request $request, Team $team, string $date): View|RedirectResponse
    {
        $weekStart = $this->weeks->alignFromRequest($team, $date);

        // Checked here rather than through a policy: the two failures need different answers.
        // Lacking the permission is a 403, but an unlocked week is a "not yet" — the manager
        // is in the right place and simply has to lock first.
        abort_unless($request->user()->hasPermissionInTeam('assignment.assign-position', $team), 403);

        if (! WeekLock::locked($team->getKey(), $weekStart)) {
            return redirect()
                ->route('calendar.show', ['date' => $weekStart->toDateString()])
                ->with(['message' => 'Najprv zamknite týždeň.', 'icon' => 'fa fa-lock']);
        }

        return view('rozpis.index', array_merge(
            $this->rozpis->forWeek($team, $weekStart),
            [
                'title' => 'ROZPIS',
                'previousWeek' => $this->weeks->previous($weekStart),
                'nextWeek' => $this->weeks->next($weekStart),
                'publishedAt' => WeekLock::forWeek($team->getKey(), $weekStart)?->rozpis_published_at,
            ],
        ));
    }

    /**
     * The finished plan, read-only — what an employee opens to find out when they work.
     *
     * Everyone in the cinema may read a published week. Before publication only the manager gets
     * in, and they see a draft banner instead: a half-built rozpis reaching the staff is worse
     * than no rozpis, because people act on it.
     */
    public function published(Request $request, Team $team, string $date): View|RedirectResponse
    {
        $weekStart = $this->weeks->alignFromRequest($team, $date);
        $lock = WeekLock::forWeek($team->getKey(), $weekStart);
        $mayBuild = $request->user()->hasPermissionInTeam('assignment.assign-position', $team);

        if (! $lock?->isRozpisPublished() && ! $mayBuild) {
            return redirect()
                ->route('calendar.show', ['date' => $weekStart->toDateString()])
                ->with([
                    'message' => 'Rozpis na tento týždeň zatiaľ nie je zverejnený.',
                    'icon' => 'fa fa-hourglass-half',
                ]);
        }

        return view('rozpis.published', [
            'title' => 'ROZPIS ZMIEN',
            'weekStart' => $weekStart,
            'weekEnd' => $this->weeks->end($weekStart),
            'previousWeek' => $this->weeks->previous($weekStart),
            'nextWeek' => $this->weeks->next($weekStart),
            'plan' => $this->rozpis->plan($team, $weekStart),
            'publishedAt' => $lock?->rozpis_published_at,
            'canBuild' => $mayBuild,
        ]);
    }

    /**
     * Copy one day's position layout onto another day.
     *
     * PositionSlotPolicy covers the target: week locked, permission held. The source only has
     * to be a date the UI actually offers, so a hand-crafted request cannot pull a layout in
     * from an arbitrary week.
     */
    public function copy(Request $request, Team $team, string $date): RedirectResponse
    {
        $target = CarbonImmutable::parse($date)->startOfDay();
        $weekStart = $this->weeks->start($team, $target);

        $this->authorize('create', [PositionSlot::class, $target]);

        $validated = $request->validate(
            ['source_date' => ['required', 'date_format:Y-m-d']],
            ['source_date.required' => 'Vyberte deň, z ktorého sa má rozpis skopírovať.'],
        );

        $source = CarbonImmutable::parse($validated['source_date'])->startOfDay();

        abort_unless(
            $this->rozpis->copySources($weekStart, $target)
                ->contains(fn (CarbonImmutable $day): bool => $day->isSameDay($source)),
            422,
        );

        $added = $this->rozpis->copySlots($source, $target);

        return redirect()
            ->route('rozpis.show', ['date' => $weekStart->toDateString()])
            ->with([
                'message' => $added === 0
                    ? 'Nepridali sa žiadne pozície — deň ich už má, alebo je zdrojový deň prázdny.'
                    : "Skopírovaných pozícií: {$added}.",
                'icon' => 'fa fa-copy',
            ]);
    }
}
