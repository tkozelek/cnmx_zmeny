<?php

namespace App\Filament\Platform\Concerns;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

trait HasBackAction
{
    protected function backAction(): Action
    {
        return Action::make('back')
            ->label('Späť')
            ->icon(Heroicon::OutlinedArrowLeft)
            ->color('gray')
            ->url(static::getResource()::getUrl('index'));
    }
}
