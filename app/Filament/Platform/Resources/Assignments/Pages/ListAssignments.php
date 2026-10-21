<?php

namespace App\Filament\Platform\Resources\Assignments\Pages;

use App\Filament\Platform\Resources\Assignments\AssignmentResource;
use Filament\Resources\Pages\ListRecords;

class ListAssignments extends ListRecords
{
    protected static string $resource = AssignmentResource::class;
}
