<?php

namespace App\Filament\Platform\Widgets;

use App\Filament\Platform\Widgets\Concerns\WeeklyChartStyle;
use App\Models\Team;
use App\Services\SignupRateService;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

/** See SignupRateService - the same numbers Správa kina shows for one cinema. */
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
        $service = app(SignupRateService::class);
        // Only the largest cinemas, in id order so each keeps its colour as sizes shift.
        $members = $service->memberCounts(limit: count(self::SERIES_COLORS))->sortKeys();
        $rates = $service->byWeekday($members);
        $names = Team::whereKey($members->keys())->pluck('name', 'id');
        $datasets = [];

        foreach ($members->keys()->values() as $slot => $teamId) {
            $datasets[] = [
                'label' => $names[$teamId] ?? "#{$teamId}",
                'data' => $rates[$teamId],
                'backgroundColor' => self::SERIES_COLORS[$slot],
                'borderRadius' => 4,
                'maxBarThickness' => 36,
            ];
        }

        return ['datasets' => $datasets, 'labels' => self::WEEKDAYS];
    }
}
