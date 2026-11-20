<?php

namespace App\Filament\Platform\Resources\BugReports\Pages;

use App\Filament\Platform\Concerns\HasBackAction;
use App\Filament\Platform\Resources\BugReports\BugReportResource;
use App\Models\BugReport;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ViewBugReport extends ViewRecord
{
    use HasBackAction;

    protected static string $resource = BugReportResource::class;

    protected function getHeaderActions(): array
    {
        /** @var BugReport $report */
        $report = $this->getRecord();
        $actions = [];

        foreach ($report->attachments as $index => $attachment) {
            $actions[] = Action::make('download'.$index)
                ->label($attachment['name'])
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action(function () use ($attachment): StreamedResponse {
                    $disk = Storage::disk(BugReport::DISK);
                    abort_unless($disk->exists($attachment['path']), 404);

                    return $disk->download($attachment['path'], $attachment['name']);
                });
        }

        return [
            $this->backAction(),
            ...$actions,
            BugReportResource::toggleResolvedAction(),
            DeleteAction::make(),
        ];
    }
}
