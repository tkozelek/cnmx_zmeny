<?php

namespace App\Exports;

use App\Models\Day;
use App\Models\User;
use App\Models\Week;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithDefaultStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Events\BeforeSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WeeklyScheduleExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithDefaultStyles, WithEvents, WithTitle
{
    protected $week;

    public function __construct(Week $week)
    {
        $this->week = $week;
    }

    public function collection()
    {
        $days = $this->week->days;
        $data = [];

        foreach ($days as $day) {
            $users = $day->users->map(function ($user) {
                $firstLetter = substr($user->name, 0, 1);
                $popis = $user->pivot->popis ? '('.$user->pivot->popis.')' : '';
                return "{$user->lastname} {$firstLetter}. $popis";
            })->toArray();
            $data[] = $users;
        }

        $transposedData = array_map(null, ...$data);

        return collect($transposedData);
    }

    public function map($row): array
    {
        return $row;
    }

    public function headings(): array
    {
        $arr = $this->week->days->pluck('date')->toArray();
        $dates = [];
        foreach ($arr as $date) {
            $dates[] = $date->format('d.m.Y')." ".$this->getDayName($date);
        }
        return $dates;
    }

    protected function getDayName($date)
    {
        Carbon::setLocale('sk');
        return Carbon::parse($date)->isoFormat('dddd');
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true
                ],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THICK]
                ]
            ]
        ];
    }

    public function defaultStyles(Style $sheet)
    {
        return [
            'alignment' => [
                'horizontal' => 'center',
                'vertical' => 'center'
            ],
            'borders' => [
                'outline' => ['borderStyle' => Border::BORDER_THIN]
            ]
        ];
    }

    public function registerEvents(): array
    {
        $additionalData = User::select('users.id as id_user', 'users.lastname as lastname', 'users.name as name', DB::raw('COUNT(user_days.id_user) as count'), 'days.id_week')
            ->leftJoin('user_days', 'users.id', '=', 'user_days.id_user')
            ->leftJoin('days', 'user_days.id_day', '=', 'days.id')
            ->where('days.id_week', $this->week->id)
            ->groupBy('users.id', 'users.lastname', 'users.name', 'days.id_week')
            ->get();
        return [
            BeforeSheet::class => function (BeforeSheet $event) {
                $event->sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
            },
            AfterSheet::class => function (AfterSheet $event) use ($additionalData) {
                $event->sheet->getDelegate()->getDefaultRowDimension()->setRowHeight(-1);
                $event->sheet->getStyle('A2:G18')->getBorders()->getAllBorders()->setBorderStyle('thin');
                $event->sheet->getDelegate()->getPageSetup()->setFitToWidth(1);
                $event->sheet->getDelegate()->getPageSetup()->setFitToHeight(0);
                $event->sheet->setTitle('Zapísané dni');

                $newSheet = $event->sheet->getParent()->createSheet()
                    ->setCellValue('A1', 'Priezvisko')
                    ->setCellValue('B1', 'Počet dni');

                $newSheet->getStyle('A1:B1')->getFont()->setBold(true);

                $newSheet->getColumnDimension('A')->setAutoSize(true);
                $newSheet->getColumnDimension('B')->setAutoSize(true);

                $newSheet->getPageSetup()->setFitToWidth(1);


                $rowIndex = 2;
                $newSheet->setTitle('Počet dni');
                foreach ($additionalData as $row) {
                    $newSheet->setCellValue('A' . $rowIndex, $row->lastname.' '.substr($row->name, 0, 1).'.')
                        ->setCellValue('B' . $rowIndex, $row->count);
                    $rowIndex++;
                }
            },

        ];
    }

    public function title(): string
    {
        return 'Zapísaný ľudia';
    }
}
