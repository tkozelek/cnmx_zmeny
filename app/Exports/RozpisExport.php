<?php

namespace App\Exports;

use App\Models\Team;
use App\Services\RozpisService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * The finished rozpis, as two sheets answering two different questions.
 *
 * "Rozpis" is the printed poster the cinema already uses: four day blocks per A4 landscape page
 * in a 2x2 grid, each a little table of meno / pozícia / čas nástupu / náhradníci. Managers print
 * it and pin it up, so the geometry is the deliverable.
 *
 * "Zoznam" is the same week as one flat row per shift, with a filter on every column. A poster
 * cannot answer "when does Kozelek work this week" - that is what the second sheet is for.
 *
 * Distinct from WeeklyScheduleExport, which exports the *signup* stage: who put their name down,
 * before anyone was placed on a position.
 */
class RozpisExport implements FromArray, WithColumnWidths, WithEvents, WithTitle
{
    /** Rows per day block, including its two header rows. Fits an A4 landscape half-page. */
    private const BAND_HEIGHT = 17;

    /** Sheet row each day block starts on. Two per page, with the page-2 title between them. */
    private const BAND_ROWS = [3, 20, 39, 56];

    /** Column offsets of the left and right block in a band: A..D and F..I, with E as the gutter. */
    private const SIDES = [0, 5];

    /**
     * Which band and side each day of the week lands in.
     *
     * Column-major inside a page, which is what the reference does: page one reads Thu/Fri down
     * the left and Sat/Sun down the right, page two Mon/Tue then Wed.
     *
     * @var list<array{int, int}>
     */
    private const LAYOUT = [[0, 0], [1, 0], [0, 1], [1, 1], [2, 0], [3, 0], [2, 1]];

    private const COLUMNS = 9;

    /** Where the second page's repeated title sits, and the row the page breaks before. */
    private const PAGE_TWO_TITLE_ROW = 37;

    /** Data rows a block can hold before it would run into the next one. */
    private const MAX_BODY_ROWS = self::BAND_HEIGHT - 2;

    /**
     * One palette, so both sheets read as one document. Deliberately light - this gets printed,
     * often in greyscale, where saturated fills turn into unreadable grey blocks.
     */
    private const INK = 'FF1F3864';

    private const INK_WARN = 'FFBF8F00';

    private const HEADER_BG = 'FFF2F2F2';

    private const STRIPE_BG = 'FFF7F9FC';

    private const GAP_BG = 'FFFDE9E9';

    private const RULE = 'FFBFBFBF';

    /** @var Collection<int, array<string, mixed>> */
    private Collection $plan;

    public function __construct(
        Team $team,
        private readonly CarbonImmutable $weekStart,
        RozpisService $rozpis,
    ) {
        $this->plan = $rozpis->plan($team, $weekStart);
    }

    /**
     * The whole poster as a fixed grid, built blank and then painted into.
     *
     * Simpler than writing cell by cell: blocks sit at known coordinates, so filling an array is
     * the same work as issuing setCellValue calls, minus the off-by-one risk of tracking a cursor.
     *
     * @return array<int, array<int, string>>
     */
    public function array(): array
    {
        $lastRow = self::BAND_ROWS[3] + self::BAND_HEIGHT - 1;
        $grid = array_fill(0, $lastRow, array_fill(0, self::COLUMNS, ''));

        // Column C, so the title sits over the middle of the page rather than the left block.
        $grid[0][2] = $this->titleText();
        $grid[self::PAGE_TWO_TITLE_ROW - 1][2] = $this->titleText();

        foreach ($this->plan as $index => $day) {
            [$band, $side] = self::LAYOUT[$index];

            $this->writeDay($grid, $day, self::BAND_ROWS[$band], self::SIDES[$side]);
        }

        return $grid;
    }

    /**
     * @return array<string, float>
     */
    public function columnWidths(): array
    {
        // Taken from the reference file - E is the narrow gutter between the two blocks.
        return [
            'A' => 19.7, 'B' => 17.7, 'C' => 14.9, 'D' => 18.7,
            'E' => 4,
            'F' => 19.7, 'G' => 17.7, 'H' => 14.9, 'I' => 18.7,
        ];
    }

    public function title(): string
    {
        return 'Rozpis';
    }

    /**
     * @return array<string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                $this->styleTitles($sheet);

                foreach ($this->plan as $index => $day) {
                    [$band, $side] = self::LAYOUT[$index];

                    $this->styleBlock($sheet, $day, self::BAND_ROWS[$band], self::SIDES[$side]);
                }

                $this->setUpPrinting($sheet);
                $this->appendListSheet($sheet);
            },
        ];
    }

    /**
     * One day block: two header rows, then a row per position with its substitutes alongside.
     *
     * Position rows are printed whether or not anybody stands in them - an empty "bufet 3" is the
     * manager's reminder that the day is short, and the reference sheet shows exactly that.
     *
     * @param  array<int, array<int, string>>  $grid
     * @param  array<string, mixed>  $day
     */
    private function writeDay(array &$grid, array $day, int $startRow, int $column): void
    {
        // The grid is 0-indexed, sheet rows are 1-indexed.
        $top = $startRow - 1;

        $grid[$top][$column] = mb_strtoupper($day['dayName']);
        $grid[$top][$column + 1] = $day['date']->format('d.m.Y');
        $grid[$top][$column + 2] = 'manažér:';
        $grid[$top][$column + 3] = $day['manager'] ?? '';

        $grid[$top + 1][$column] = 'meno';
        $grid[$top + 1][$column + 1] = 'pozícia';
        $grid[$top + 1][$column + 2] = 'čas nástupu';
        $grid[$top + 1][$column + 3] = 'náhradníci';

        $rows = $day['rows'];
        $substitutes = $day['substitutes'];

        if ($rows === [] && $substitutes === []) {
            $grid[$top + 2][$column] = 'Žiadne pozície';

            return;
        }

        $needed = max(count($rows), count($substitutes));
        $height = min($needed, self::MAX_BODY_ROWS);

        for ($i = 0; $i < $height; $i++) {
            $row = $top + 2 + $i;

            if (isset($rows[$i])) {
                // An em dash rather than a blank: an unfilled row must read as "nobody yet", not
                // as a cell somebody forgot to write to.
                $grid[$row][$column] = $rows[$i]['name'] ?? '-';
                $grid[$row][$column + 1] = $rows[$i]['label'];
                $grid[$row][$column + 2] = $rows[$i]['time'] ?? '';
            }

            $grid[$row][$column + 3] = $substitutes[$i] ?? '';
        }

        // A day taller than its block would overwrite the neighbouring one, so it is truncated -
        // but never silently. The last row says what is missing, and Zoznam has all of it.
        if ($needed > self::MAX_BODY_ROWS) {
            $grid[$top + 1 + self::MAX_BODY_ROWS][$column] =
                '+ '.($needed - self::MAX_BODY_ROWS).' ďalších - pozri hárok Zoznam';
        }
    }

    private function titleText(): string
    {
        return 'ROZPIS ZMIEN BRIGÁDNIKOV od '
            .$this->weekStart->format('j.n.').' - '
            .$this->weekStart->addDays(6)->format('j.n.Y');
    }

    private function styleTitles(Worksheet $sheet): void
    {
        foreach ([1, self::PAGE_TWO_TITLE_ROW] as $row) {
            $range = 'C'.$row.':G'.($row + 1);

            $sheet->mergeCells($range);
            $sheet->getStyle($range)->applyFromArray([
                'font' => ['bold' => true, 'size' => 15, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::INK]],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            $sheet->getRowDimension($row)->setRowHeight(15);
            $sheet->getRowDimension($row + 1)->setRowHeight(15);
        }
    }

    /**
     * Borders, fills and row heights for one day block.
     *
     * @param  array<string, mixed>  $day
     */
    private function styleBlock(Worksheet $sheet, array $day, int $startRow, int $column): void
    {
        $first = $this->columnLetter($column);
        $last = $this->columnLetter($column + 3);
        $endRow = $startRow + self::BAND_HEIGHT - 1;
        $bodyRows = min(max(count($day['rows']), count($day['substitutes'])), self::MAX_BODY_ROWS);

        $sheet->getStyle($first.$startRow.':'.$last.$endRow)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => self::RULE]]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            'font' => ['size' => 11],
        ]);

        // The day's headline. Amber when the day still has an empty position - one glance across
        // the printed page then shows which days need work.
        $sheet->getStyle($first.$startRow.':'.$last.$startRow)->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => $day['unfilled'] === 0 ? self::INK : self::INK_WARN],
            ],
        ]);

        $sheet->getStyle($first.($startRow + 1).':'.$last.($startRow + 1))->applyFromArray([
            'font' => ['bold' => true, 'italic' => true, 'size' => 10, 'color' => ['argb' => 'FF595959']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::HEADER_BG]],
        ]);

        $sheet->getRowDimension($startRow)->setRowHeight(20);
        $sheet->getRowDimension($startRow + 1)->setRowHeight(16);

        for ($i = 0; $i < $bodyRows; $i++) {
            $row = $startRow + 2 + $i;
            $unfilled = isset($day['rows'][$i]) && $day['rows'][$i]['name'] === null;

            // Unfilled beats striping: a gap in the plan is the one thing worth spotting.
            $background = match (true) {
                $unfilled => self::GAP_BG,
                $i % 2 === 1 => self::STRIPE_BG,
                default => null,
            };

            if ($background !== null) {
                $sheet->getStyle($first.$row.':'.$last.$row)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $background]],
                ]);
            }

            // Where one group ends and the next begins. A rule rather than a heading row: a day
            // block holds a fixed number of rows, and spending one per group on a word the
            // position names already imply is how a busy Friday overflows.
            if (($day['rows'][$i]['startsGroup'] ?? false) && $i > 0) {
                $sheet->getStyle($first.$row.':'.$last.$row)->applyFromArray([
                    'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => self::INK]]],
                ]);
            }

            $sheet->getRowDimension($row)->setRowHeight(16);
        }

        // Names carry the page, so they are the only bold thing in the body.
        $sheet->getStyle($first.($startRow + 2).':'.$first.$endRow)->getFont()->setBold(true);

        // Times read as a column of numbers, so they line up rather than ragging left.
        $times = $this->columnLetter($column + 2);
        $sheet->getStyle($times.($startRow + 2).':'.$times.$endRow)
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Substitutes are context, not the plan - quieter than the rows they sit beside.
        $spares = $this->columnLetter($column + 3);
        $sheet->getStyle($spares.($startRow + 2).':'.$spares.$endRow)->applyFromArray([
            'font' => ['size' => 10, 'italic' => true, 'color' => ['argb' => 'FF7F7F7F']],
        ]);

        $sheet->getStyle($first.$startRow.':'.$last.$endRow)->applyFromArray([
            'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => self::INK]]],
        ]);
    }

    private function setUpPrinting(Worksheet $sheet): void
    {
        $setup = $sheet->getPageSetup();

        $setup->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $setup->setPaperSize(PageSetup::PAPERSIZE_A4);
        $setup->setFitToWidth(1);
        $setup->setFitToHeight(0);
        $setup->setHorizontalCentered(true);

        $sheet->getPageMargins()->setTop(0.3)->setBottom(0.3)->setLeft(0.3)->setRight(0.3);

        // The blocks are already ruled; sheet gridlines on top of them only add noise on paper.
        $sheet->setShowGridlines(false);
        $sheet->getTabColor()->setARGB(self::INK);

        // Four day blocks per sheet of paper; the second title is the top of page two.
        $sheet->setBreak('A'.self::PAGE_TWO_TITLE_ROW, Worksheet::BREAK_ROW);
    }

    /**
     * The same week as one row per shift, filterable and sortable.
     *
     * Appended here rather than as a second export class, matching WeeklyScheduleExport - the
     * sheet is a dozen lines of data and needs none of the concern plumbing.
     */
    private function appendListSheet(Worksheet $poster): void
    {
        $sheet = $poster->getParent()->createSheet();
        $sheet->setTitle('Zoznam');

        foreach (['Dátum', 'Deň', 'Skupina', 'Pozícia', 'Meno', 'Čas nástupu'] as $index => $heading) {
            $sheet->setCellValue($this->columnLetter($index).'1', $heading);
        }

        $row = 2;

        foreach ($this->plan as $day) {
            // The vedúci leads the day here even though the poster keeps them out of the body -
            // a flat list of every shift is exactly where they should still be findable.
            foreach ([...$day['managerRows'], ...$day['rows']] as $entry) {
                $this->writeListRow(
                    $sheet, $row++, $day,
                    $entry['group'] ?? '', $entry['label'], $entry['name'] ?? '-', $entry['time'] ?? '',
                );
            }

            // Substitutes belong here too - "who else could have covered Friday" is exactly the
            // kind of question the poster cannot answer.
            foreach ($day['substitutes'] as $name) {
                $this->writeListRow($sheet, $row++, $day, '', 'náhradník', $name, '');
            }
        }

        $lastRow = max(2, $row - 1);

        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::INK]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(22);

        $sheet->getStyle('A1:F'.$lastRow)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => self::RULE]]],
        ]);

        $sheet->getStyle('A2:A'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F2:F'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E2:E'.$lastRow)->getFont()->setBold(true);

        foreach (['A' => 12, 'B' => 14, 'C' => 18, 'D' => 22, 'E' => 24, 'F' => 14] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        // The three things that make it usable: a filter on every column - including Skupina, so
        // "show me the whole bufet this week" is two clicks - and a header that stays put.
        $sheet->setAutoFilter('A1:F'.$lastRow);
        $sheet->freezePane('A2');

        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);
    }

    /**
     * @param  array<string, mixed>  $day
     */
    private function writeListRow(Worksheet $sheet, int $row, array $day, string $group, string $position, string $name, string $time): void
    {
        $sheet->setCellValue('A'.$row, $day['date']->format('d.m.Y'));
        $sheet->setCellValue('B'.$row, $day['dayName']);
        $sheet->setCellValue('C'.$row, $group);
        $sheet->setCellValue('D'.$row, $position);
        $sheet->setCellValue('E'.$row, $name);
        $sheet->setCellValue('F'.$row, $time);
    }

    private function columnLetter(int $index): string
    {
        return chr(ord('A') + $index);
    }
}
