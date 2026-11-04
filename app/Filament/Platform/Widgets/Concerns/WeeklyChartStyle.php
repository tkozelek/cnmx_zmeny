<?php

namespace App\Filament\Platform\Widgets\Concerns;

use App\Support\SlovakDate;
use Carbon\CarbonImmutable;

/** Shared look of the dashboard's week-by-week charts (dark panel, palette validated for CVD). */
trait WeeklyChartStyle
{
    /** Categorical slots in fixed order - never cycled; anything past them folds into "Ostatné". */
    protected const array SERIES_COLORS = ['#3987e5', '#d95926', '#199e70', '#c98500', '#d55181'];

    protected const string OTHER_COLOR = '#71717a';

    protected const int WEEKS = 12;

    /** @return list<CarbonImmutable> Monday of each of the last WEEKS weeks, oldest first. */
    protected function weekStarts(): array
    {
        $current = CarbonImmutable::now()->startOfWeek();

        return array_map(fn (int $i): CarbonImmutable => $current->subWeeks(self::WEEKS - 1 - $i), range(0, self::WEEKS - 1));
    }

    /**
     * @param  list<CarbonImmutable>  $weeks
     * @return list<string>
     */
    protected function weekLabels(array $weeks): array
    {
        return array_map(fn (CarbonImmutable $week): string => SlovakDate::short($week), $weeks);
    }

    /** @return array<string, mixed> */
    protected function chartOptions(bool $legend): array
    {
        $grid = ['color' => 'rgba(255, 255, 255, 0.06)'];
        $ticks = ['color' => '#a1a1aa'];

        return [
            'maintainAspectRatio' => false,
            'interaction' => ['mode' => 'index', 'intersect' => false],
            'plugins' => [
                'legend' => ['display' => $legend, 'labels' => ['color' => '#d4d4d8', 'usePointStyle' => true, 'boxWidth' => 8]],
                'tooltip' => ['mode' => 'index', 'intersect' => false],
            ],
            'scales' => [
                'x' => ['grid' => ['display' => false], 'ticks' => $ticks],
                'y' => ['beginAtZero' => true, 'grid' => $grid, 'ticks' => [...$ticks, 'precision' => 0]],
            ],
        ];
    }
}
