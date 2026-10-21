<?php

namespace App\Filament\Platform\Resources\Absences\Pages;

use App\Filament\Platform\Resources\Absences\AbsenceResource;
use Filament\Resources\Pages\ListRecords;

class ListAbsences extends ListRecords
{
    protected static string $resource = AbsenceResource::class;
}
