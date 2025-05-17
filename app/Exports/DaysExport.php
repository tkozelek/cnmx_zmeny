<?php

namespace App\Exports;

use App\Models\Day;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DaysExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Day::all();
    }

    public function headings(): array
    {
        return ['caw', 'caw', 'caw'];
    }
}
