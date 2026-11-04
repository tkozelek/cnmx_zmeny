<?php

namespace App\Filament\Platform\Widgets;

use App\Filament\Platform\Widgets\Concerns\WeeklyChartStyle;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;

class NewAccountsPerWeekChart extends ChartWidget
{
    use WeeklyChartStyle;

    protected static ?int $sort = 3;

    protected ?string $heading = 'Nové účty po týždňoch';

    protected ?string $description = 'Registrácie a účty založené manažérom.';

    protected ?string $maxHeight = '280px';

    protected ?string $pollingInterval = null;

    protected function getType(): string
    {
        return 'bar';
    }

    /** @return array<string, mixed> */
    protected function getOptions(): array
    {
        return $this->chartOptions(legend: false);
    }

    /** @return array<string, mixed> */
    protected function getData(): array
    {
        $weeks = $this->weekStarts();

        $perWeek = User::query()
            ->where('is_super_admin', false)
            ->where('created_at', '>=', $weeks[0])
            ->pluck('created_at')
            ->countBy(fn (CarbonImmutable|\DateTimeInterface $createdAt): string => CarbonImmutable::parse($createdAt)->startOfWeek()->toDateString());

        return [
            'datasets' => [[
                'label' => 'Nové účty',
                'data' => array_map(fn (CarbonImmutable $week): int => $perWeek[$week->toDateString()] ?? 0, $weeks),
                'backgroundColor' => self::SERIES_COLORS[0],
                'borderRadius' => 4,
                'maxBarThickness' => 28,
            ]],
            'labels' => $this->weekLabels($weeks),
        ];
    }
}
