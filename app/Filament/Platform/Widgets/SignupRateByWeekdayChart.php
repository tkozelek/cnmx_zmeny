<?php

namespace App\Filament\Platform\Widgets;

use App\Filament\Platform\Widgets\Concerns\WeeklyChartStyle;
use App\Models\Team;
use Carbon\CarbonImmutable;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

/**
 * Share of a cinema's members signed up on an average Monday, Tuesday, ... - normalised by
 * cinema size, so a small and a large cinema compare on one scale.
 */
class SignupRateByWeekdayChart extends ChartWidget
{
    use WeeklyChartStyle;

    private const array WEEKDAYS = ['Po', 'Ut', 'St', 'Št', 'Pi', 'So', 'Ne'];

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Obsadenosť podľa dňa v týždni';

    protected ?string $description = 'Priemerný podiel členov kina zapísaných v daný deň, posledných 8 týždňov.';

    protected ?string $maxHeight = '300px';

    protected ?string $pollingInterval = null;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): RawJs
    {
        $legend = count($this->getCachedData()['datasets']) > 1 ? 'true' : 'false';

        return RawJs::make(<<<JS
            {
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: {$legend}, labels: { color: '#d4d4d8', usePointStyle: true, boxWidth: 8 } },
                    tooltip: { callbacks: { label: (ctx) => ctx.dataset.label + ': ' + ctx.parsed.y.toLocaleString('sk-SK', { maximumFractionDigits: 1 }) + ' %' } },
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#a1a1aa' } },
                    y: { beginAtZero: true, suggestedMax: 100, grid: { color: 'rgba(255, 255, 255, 0.06)' }, ticks: { color: '#a1a1aa', callback: (value) => value + ' %' } },
                },
            }
        JS);
    }

    /** @return array<string, mixed> */
    protected function getData(): array
    {
        $to = CarbonImmutable::yesterday();
        $from = $to->subWeeks(8)->addDay();

        // Members only of the largest cinemas, so a share is always over a real group of people.
        $members = DB::table('team_user')
            ->join('users', 'users.id', '=', 'team_user.user_id')
            ->whereNotNull('team_user.approved_at')
            ->where('users.is_active', true)
            ->where('users.is_super_admin', false)
            ->selectRaw('team_user.team_id, count(*) as total')
            ->groupBy('team_user.team_id')
            ->orderByDesc('total')
            ->limit(count(self::SERIES_COLORS))
            ->pluck('total', 'team_id')
            ->map(fn (mixed $total): int => (int) $total)
            ->filter()
            ->sortKeys();

        $perDay = DB::table('assignments')
            ->whereIn('team_id', $members->keys())
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('team_id, date, count(*) as total')
            ->groupBy('team_id', 'date')
            ->get();

        // How many Mondays, Tuesdays, ... the window holds - 8 each for a whole number of weeks.
        $daysPerWeekday = array_fill(1, 7, 0);
        for ($day = $from; $day->lte($to); $day = $day->addDay()) {
            $daysPerWeekday[$day->dayOfWeekIso]++;
        }

        $sums = [];
        foreach ($perDay as $row) {
            $weekday = CarbonImmutable::parse($row->date)->dayOfWeekIso;
            $sums[$row->team_id][$weekday] = ($sums[$row->team_id][$weekday] ?? 0) + (int) $row->total;
        }

        $names = Team::whereKey($members->keys())->pluck('name', 'id');
        $datasets = [];

        foreach ($members->keys()->values() as $slot => $teamId) {
            $datasets[] = [
                'label' => $names[$teamId] ?? "#{$teamId}",
                'data' => array_map(
                    fn (int $weekday): float => round(($sums[$teamId][$weekday] ?? 0) / $daysPerWeekday[$weekday] / $members[$teamId] * 100, 1),
                    range(1, 7),
                ),
                'backgroundColor' => self::SERIES_COLORS[$slot],
                'borderRadius' => 4,
                'maxBarThickness' => 36,
            ];
        }

        return ['datasets' => $datasets, 'labels' => self::WEEKDAYS];
    }
}
