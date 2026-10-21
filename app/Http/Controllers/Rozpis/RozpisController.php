<?php

namespace App\Http\Controllers\Rozpis;

use App\Http\Controllers\Controller;
use App\Models\PositionSlot;
use App\Models\Team;
use App\Models\WeekLock;
use App\Services\RozpisService;
use App\Services\WeekService;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * The manager's step: turning a locked week's signups into an actual shift plan.
 *
 * Gated the opposite way to the calendar - the builder opens only *once the week is locked*,
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

        abort_unless($request->user()->canBuildRozpis(), 403);

        if (! WeekLock::locked($team->getKey(), $weekStart)) {
            return redirect()
                ->route('calendar.show', ['date' => $weekStart->toDateString()])
                ->with(['message' => 'Najprv zamkni týždeň.', 'icon' => 'fa fa-lock']);
        }

        return view('rozpis.index', array_merge(
            $this->rozpis->forWeek($team, $weekStart),
            [
                'title' => 'ROZPIS',
                // The help panel prints the cinema's live settings rather than the defaults.
                'team' => $team,
                'guide' => $this->guide($team),
                'previousWeek' => $this->weeks->previous($weekStart),
                'nextWeek' => $this->weeks->next($weekStart),
                'publishedAt' => WeekLock::forWeek($team->getKey(), $weekStart)?->rozpis_published_at,
            ],
        ));
    }

    /**
     * The finished plan, read-only - what an employee opens to find out when they work.
     *
     * Everyone in the cinema may read a published week. Before publication only the manager gets
     * in, and they see a draft banner instead: a half-built rozpis reaching the staff is worse
     * than no rozpis, because people act on it.
     */
    public function published(Request $request, Team $team, string $date): View|RedirectResponse
    {
        $weekStart = $this->weeks->alignFromRequest($team, $date);
        $lock = WeekLock::forWeek($team->getKey(), $weekStart);
        $mayBuild = $request->user()->canBuildRozpis();

        if (! $lock?->isRozpisPublished() && ! $mayBuild) {
            return redirect()
                ->route('calendar.show', ['date' => $weekStart->toDateString()])
                ->with([
                    'message' => 'Rozpis na tento týždeň zatiaľ nie je zverejnený.',
                    'icon' => 'fa fa-hourglass-half',
                ]);
        }

        $plan = $this->markViewer($this->rozpis->plan($team, $weekStart, preferCode: false), $request->user()->getKey());

        return view('rozpis.published', [
            'title' => 'ROZPIS ZMIEN',
            'weekStart' => $weekStart,
            'weekEnd' => $this->weeks->end($weekStart),
            'previousWeek' => $this->weeks->previous($weekStart),
            'nextWeek' => $this->weeks->next($weekStart),
            // Full position name, not the builder's short code - this is what an employee reads
            // to find out when they work, same as the printed Excel sheet it mirrors.
            'plan' => $plan,
            'isEmpty' => $plan->every(fn (array $day): bool => $day['rows'] === []),
            'todayAnchor' => $plan->first(fn (array $day): bool => $day['isToday'])['date'] ?? null,
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
        try {
            $target = CarbonImmutable::parse($date)->startOfDay();
        } catch (InvalidFormatException) {
            abort(404);
        }
        $weekStart = $this->weeks->start($team, $target);

        $this->authorize('create', [PositionSlot::class, $target]);

        $validated = $request->validate(
            ['source_date' => ['required', 'date_format:Y-m-d']],
            ['source_date.required' => 'Vyber deň, z ktorého sa má rozpis skopírovať.'],
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
                    ? 'Nepridali sa žiadne pozície – deň ich už má, alebo je zdrojový deň prázdny.'
                    : "Skopírovaných pozícií: {$added}.",
                'icon' => 'fa fa-copy',
            ]);
    }

    /**
     * Copy the entire week's position slot structure from the previous week.
     */
    public function copyWeek(Request $request, Team $team, string $date): RedirectResponse
    {
        $targetWeekStart = $this->weeks->alignFromRequest($team, $date);
        $previousWeekStart = $this->weeks->previous($targetWeekStart);

        $this->authorize('create', [PositionSlot::class, $targetWeekStart]);

        $added = $this->rozpis->copyWeekSlots($team, $previousWeekStart, $targetWeekStart);

        return redirect()
            ->route('rozpis.show', ['date' => $targetWeekStart->toDateString()])
            ->with([
                'message' => $added === 0
                    ? 'Nepridali sa žiadne pozície z minulého týždňa – týždeň ich už má, alebo bol minulý týždeň prázdny.'
                    : "Skopírovaných pozícií z minulého týždňa: {$added}.",
                'icon' => 'fa fa-copy',
            ]);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $plan
     * @return Collection<int, array<string, mixed>>
     */
    private function markViewer(Collection $plan, int $viewerId): Collection
    {
        $isMine = fn (array $row): bool => $row['user_id'] !== null && $row['user_id'] === $viewerId;

        return $plan->map(fn (array $day): array => [
            ...$day,
            'isToday' => $day['date']->isToday(),
            'isMine' => collect($day['rows'])->contains($isMine) || collect($day['managerRows'])->contains($isMine),
            'managerIsMe' => isset($day['managerRows'][0]) && $isMine($day['managerRows'][0]),
            'rows' => array_map(fn (array $row): array => [...$row, 'isMine' => $isMine($row)], $day['rows']),
        ]);
    }

    /**
     * @return array{settings: list<array{0: string, 1: string, 2: string}>, dayWeights: list<array{short: string, weight: float, isHard: bool}>, averageWeight: float, windowWeeks: int}
     */
    private function guide(Team $team): array
    {
        $weekDayNames = ['Pondelok', 'Utorok', 'Streda', 'Štvrtok', 'Piatok', 'Sobota', 'Nedeľa'];
        $weights = $team->fairnessDayWeights();
        $averageWeight = array_sum($weights) / count($weights);

        return [
            'settings' => [
                ['Názov kina', $team->name, 'Zobrazuje sa v hlavičke a v exportoch.'],
                ['Začiatok týždňa', $weekDayNames[$team->weekStartDay()] ?? '-', 'Deň, ktorým sa začína pracovný týždeň – od neho sa odvíja aj rozpis.'],
                ['Počet týždňov dopredu', $team->weekLookahead().' týž.', 'Koľko týždňov do budúcna sa zamestnanci môžu zapisovať.'],
                ['Uzávierka absencií', $team->absenceDeadlineDays().' dní', 'Koľko dní pred prvým dňom absencie sa dá ešte nahlásiť.'],
                ['Uchovanie absencie', $team->staleAbsenceDeletionDays().' dní', 'Ako dlho po skončení sa neaktívna absencia dá ešte zmazať. 0 = bez čakania.'],
                ['Obdobie hodnotenia', $team->fairnessWindowWeeks().' týž.', 'Ako ďaleko do minulosti sa počítajú odpracované dni pre zásluhy aj pre poradie na neobľúbené dni.'],
            ],
            'dayWeights' => array_map(fn (string $name, float $weight): array => [
                'short' => mb_substr($name, 0, 3).'.',
                'weight' => $weight,
                'isHard' => $weight > $averageWeight,
            ], $weekDayNames, $weights),
            'averageWeight' => $averageWeight,
            'windowWeeks' => $team->fairnessWindowWeeks(),
        ];
    }
}
