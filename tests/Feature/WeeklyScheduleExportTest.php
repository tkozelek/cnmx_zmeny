<?php

namespace Tests\Feature;

use App\Exports\WeeklyScheduleExport;
use App\Models\Day;
use App\Models\User;
use App\Models\Week;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class WeeklyScheduleExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_escapes_formula_prefixes_in_names(): void
    {
        $week = Week::forceCreate(['date_from' => '2025-10-06', 'date_to' => '2025-10-12', 'locked' => 0]);
        $days = collect(range(6, 12))->map(fn (int $d) => Day::forceCreate(['date' => sprintf('2025-10-%02d', $d), 'id_week' => $week->id]));
        $day = $days->first();

        foreach (['=cmd', '+cmd', '-cmd', '@cmd'] as $lastname) {
            $user = User::factory()->create(['lastname' => $lastname, 'name' => 'Jan', 'id_role' => config('constants.roles.brigadnik')]);
            $day->users()->attach($user->id);
        }

        Storage::fake('local');
        Excel::store(new WeeklyScheduleExport($week->fresh()), 'week.xlsx', 'local');
        $spreadsheet = IOFactory::load(Storage::disk('local')->path('week.xlsx'));

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            foreach ($sheet->getRowIterator(2) as $row) {
                $cell = $sheet->getCell('A'.$row->getRowIndex());
                if ($cell->getValue() === null) {
                    continue;
                }
                $this->assertNotSame(DataType::TYPE_FORMULA, $cell->getDataType());
                $this->assertStringStartsWith("'", (string) $cell->getValue());
            }
        }
    }
}
