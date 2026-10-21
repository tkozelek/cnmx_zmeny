<?php

namespace App\Filament\Platform\Resources\Teams\Pages;

use App\Filament\Platform\Resources\Teams\TeamResource;
use App\Models\Team;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;

class EditTeam extends EditRecord
{
    protected static string $resource = TeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->modalDescription('Vymaže kino so všetkými zápismi, absenciami a zámkami týždňov. Nedá sa to vrátiť.')
                ->schema([
                    TextInput::make('confirm_name')
                        ->label(fn (Team $record): string => "Pre potvrdenie napíš „{$record->name}“")
                        ->required()
                        ->in(fn (Team $record): array => [$record->name]),
                ]),
        ];
    }
}
