<?php

namespace App\Filament\Platform\Resources\Teams\Pages;

use App\Filament\Platform\Resources\Teams\TeamResource;
use App\Models\TeamSetting;
use Filament\Resources\Pages\CreateRecord;

class CreateTeam extends CreateRecord
{
    protected static string $resource = TeamResource::class;

    protected function afterCreate(): void
    {
        TeamSetting::withoutGlobalScope('team')->create(['team_id' => $this->getRecord()->getKey()]);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
