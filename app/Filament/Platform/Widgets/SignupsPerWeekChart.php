<?php

namespace App\Filament\Platform\Widgets;

use App\Filament\Platform\Widgets\Concerns\WeeklyChartStyle;
use App\Models\Team;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class SignupsPerWeekChart extends ChartWidget
{
    use WeeklyChartStyle;

    protected static ?int $sort = 2;

    protected ?string $heading = 'Zápisy po týždňoch';

    protected ?string $description = 'Zapísané dni podľa dátumu zmeny, týždne od pondelka.';

    protected ?string $maxHeight = '280px';

    protected ?string $pollingInterval = null;

    protected function getType(): string
    {
        return 'line';
    }

    /** @return array<string, mixed> */
    protected function getOptions(): array
    {
        return $this->chartOptions(legend: true);
    }

    /** @return array<string, mixed> */
    protected function getData(): array
    {
        $weeks = $this->weekStarts();
        $weekIndex = array_flip(array_map(fn (CarbonImmutable $week): string => $week->toDateString(), $weeks));

        // Grouped per day in SQL (portable), folded into weeks here - a week's worth of rows per cinema at most.
        $perDay = DB::table('assignments')
            ->whereBetween('date', [$weeks[0]->toDateString(), end($weeks)->addDays(6)->toDateString()])
            ->selectRaw('team_id, date, count(*) as total')
            ->groupBy('team_id', 'date')
            ->get();

        $counts = [];

        foreach ($perDay as $row) {
            $week = CarbonImmutable::parse($row->date)->startOfWeek()->toDateString();
            $counts[$row->team_id] ??= array_fill(0, count($weeks), 0);
            $counts[$row->team_id][$weekIndex[$week]] += (int) $row->total;
        }

        // The busiest cinemas get a colour each, listed in id order so a cinema keeps its colour
        // while the ranking around it shifts; the rest fold into one gray "Ostatné" line.
        uasort($counts, fn (array $a, array $b): int => array_sum($b) <=> array_sum($a));
        $shown = array_slice($counts, 0, count(self::SERIES_COLORS), preserve_keys: true);
        $rest = array_diff_key($counts, $shown);
        ksort($shown);

        $names = Team::whereKey(array_keys($shown))->pluck('name', 'id');
        $datasets = [];

        foreach (array_keys($shown) as $slot => $teamId) {
            $datasets[] = $this->line($names[$teamId] ?? "#{$teamId}", $shown[$teamId], self::SERIES_COLORS[$slot]);
        }

        if ($rest !== []) {
            $datasets[] = $this->line('Ostatné', array_map(fn (int ...$week): int => array_sum($week), ...array_values($rest)), self::OTHER_COLOR);
        }

        return ['datasets' => $datasets, 'labels' => $this->weekLabels($weeks)];
    }

    /**
     * @param  list<int>  $data
     * @return array<string, mixed>
     */
    private function line(string $label, array $data, string $color): array
    {
        return [
            'label' => $label,
            'data' => $data,
            'borderColor' => $color,
            'backgroundColor' => $color,
            'borderWidth' => 2,
            'pointRadius' => 3,
            'pointHoverRadius' => 5,
            'tension' => 0.25,
        ];
    }
}
