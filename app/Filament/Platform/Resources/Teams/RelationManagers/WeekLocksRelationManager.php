<?php

namespace App\Filament\Platform\Resources\Teams\RelationManagers;

use App\Models\WeekLock;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WeekLocksRelationManager extends RelationManager
{
    protected static string $relationship = 'weekLocks';

    protected static ?string $title = 'Zamknuté týždne';

    protected static ?string $modelLabel = 'zámok týždňa';

    protected static bool $shouldSkipAuthorization = true;

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('week_start', 'desc')
            ->columns([
                TextColumn::make('week_start')->label('Týždeň od')->date('d.m.Y')->sortable(),
                TextColumn::make('lockedBy.email')->label('Zamkol')->placeholder('—'),
                TextColumn::make('created_at')->label('Zamknuté')->dateTime('d.m.Y H:i'),
                TextColumn::make('is_locked')->label('Stav')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Zamknutý' : 'Odomknutý, rozpis zverejnený'),
            ])
            ->recordActions([
                Action::make('unlock')
                    ->label('Odomknúť')
                    ->requiresConfirmation()
                    ->modalHeading('Odomknúť týždeň')
                    ->modalDescription(fn (WeekLock $record): ?string => $record->isRozpisPublished() ? 'Rozpis ostane zverejnený.' : null)
                    ->visible(fn (WeekLock $record): bool => $record->is_locked)
                    ->action(fn (WeekLock $record) => $record->unlock()),
            ]);
    }
}
