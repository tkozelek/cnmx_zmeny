<?php

namespace App\Filament\Platform\Resources\Absences;

use App\Enums\AbsenceStatus;
use App\Filament\Platform\Resources\Absences\Pages\ListAbsences;
use App\Models\Absence;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class AbsenceResource extends Resource
{
    protected static ?string $model = Absence::class;

    protected static bool $shouldSkipAuthorization = true;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $modelLabel = 'absencia';

    protected static ?string $pluralModelLabel = 'absencie';

    protected static string|UnitEnum|null $navigationGroup = 'Prevádzka';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('date_from', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['team', 'user']))
            ->columns([
                TextColumn::make('team.name')->label('Kino')->sortable(),
                TextColumn::make('user.email')->label('Používateľ')->searchable(),
                TextColumn::make('date_from')->label('Od')->date('d.m.Y')->sortable(),
                TextColumn::make('date_to')->label('Do')->date('d.m.Y')->sortable(),
                TextColumn::make('status')->label('Stav')->badge()
                    ->formatStateUsing(fn (AbsenceStatus $state): string => $state->label()),
                TextColumn::make('reason')->label('Dôvod')->limit(40)->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('team')->label('Kino')->relationship('team', 'name'),
                SelectFilter::make('status')->label('Stav')
                    ->options(collect(AbsenceStatus::cases())->mapWithKeys(fn (AbsenceStatus $s): array => [$s->value => $s->label()])->all()),
            ])
            ->recordActions([
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAbsences::route('/'),
        ];
    }
}
