<?php

namespace App\Filament\Platform\Resources\BugReports\Pages;

use App\Filament\Platform\Resources\BugReports\BugReportResource;
use Filament\Resources\Pages\ListRecords;

class ListBugReports extends ListRecords
{
    protected static string $resource = BugReportResource::class;
}
