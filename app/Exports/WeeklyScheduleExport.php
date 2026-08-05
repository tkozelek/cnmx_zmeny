<?php

namespace App\Exports;

use App\Models\Assignment;
use App\Models\Team;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithDefaultStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Events\BeforeSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * One week's plan: a column per day, the people signed up listed underneath, plus a second
 * sheet counting days per person.
 *
 * Built from `assignments` - the legacy version walked Week -> Day -> user_days.
 */
class WeeklyScheduleExport implements FromCollection, ShouldAutoSize, WithDefaultStyles, WithEvents, WithHeadings, WithStyles, WithTitle
{
    /** @var Collection<string, Collection<int, Assignment>> */
    private Collection $byDate;

    public function __construct(
        private readonly Team $team,
        private readonly CarbonImmutable $weekStart,
    ) {
        $this->byDate = Assignment::with(['user', 'position'])
            ->betweenDates($this->weekStart, $this->weekEnd())
            ->get()
            ->groupBy(fn (Assignment $assignment): string => $assignment->date->toDateString());
    }

    /**
     * The grid, transposed: a spreadsheet is written row by row but the layout is a column
     * per day, so the day columns are zipped into rows here.
     */
    public function collection(): Collection
    {
        $columns = $this->dates()
            ->map(fn (CarbonImmutable $date): array => $this->cellsFor($date))
            ->all();

        $rowCount = max(array_map('count', $columns) ?: [0]);

        return collect(range(0, max(0, $rowCount - 1)))->map(
            fn (int $row): array => array_map(fn (array $column): string => $column[$row] ?? '', $columns)
        );
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return $this->dates()
            ->map(fn (CarbonImmutable $date): string => $date->format('d.m.Y').' '.$date->locale('sk')->isoFormat('dddd'))
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THICK]],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultStyles(Style $style): array
    {
        return [
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
            'borders' => ['outline' => ['borderStyle' => Border::BORDER_THIN]],
        ];
    }

    /**
     * @return array<string, callable>
     */
    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function (BeforeSheet $event): void {
                $event->sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
            },

            AfterSheet::class => function (AfterSheet $event): void {
                $lastRow = $this->byDate->max(fn (Collection $day): int => $day->count()) + 1;

                $event->sheet->getDelegate()->getDefaultRowDimension()->setRowHeight(-1);
                $event->sheet->getStyle('A2:G'.max(2, $lastRow))
                    ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $event->sheet->getDelegate()->getPageSetup()->setFitToWidth(1);
                $event->sheet->getDelegate()->getPageSetup()->setFitToHeight(0);
                $event->sheet->setTitle('Zapísané dni');

                $this->appendDayCountSheet($event);
            },
        ];
    }

    public function title(): string
    {
        return 'Zapísaní ľudia';
    }

    /** "Kozelek T. (RN)" - surname, initial, position code. */
    private function cellsFor(CarbonImmutable $date): array
    {
        return $this->byDate->get($date->toDateString(), collect())
            ->sortBy(fn (Assignment $a): int => $a->position?->sort_order ?? 0)
            ->map(function (Assignment $assignment): string {
                $code = $assignment->position?->label();

                return trim($assignment->user.($code ? " ({$code})" : ''));
            })
            ->values()
            ->all();
    }

    /** A second sheet: how many days each person took, most first. */
    private function appendDayCountSheet(AfterSheet $event): void
    {
        $counts = $this->byDate
            ->flatten(1)
            ->groupBy('user_id')
            ->map(fn (Collection $rows): array => [
                'name' => (string) $rows->first()->user,
                'count' => $rows->pluck('date')->unique()->count(),
            ])
            ->sortByDesc('count');

        $sheet = $event->sheet->getParent()->createSheet()
            ->setCellValue('A1', 'Priezvisko')
            ->setCellValue('B1', 'Počet dní');

        $sheet->getStyle('A1:B1')->getFont()->setBold(true);
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->setTitle('Počet dní');

        $row = 2;
        foreach ($counts as $entry) {
            $sheet->setCellValue('A'.$row, $entry['name'])->setCellValue('B'.$row, $entry['count']);
            $row++;
        }
    }

    /**
     * @return Collection<int, CarbonImmutable>
     */
    private function dates(): Collection
    {
        return collect(range(0, 6))->map(fn (int $i): CarbonImmutable => $this->weekStart->addDays($i));
    }

    private function weekEnd(): CarbonImmutable
    {
        return $this->weekStart->addDays(6);
    }
}
