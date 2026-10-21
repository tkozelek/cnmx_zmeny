<?php

namespace App\Filament\Platform\Resources\Activities;

use App\Filament\Platform\Resources\Activities\Pages\ListActivities;
use App\Services\PlatformAudit;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;
use UnitEnum;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static bool $shouldSkipAuthorization = true;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $modelLabel = 'záznam auditu';

    protected static ?string $pluralModelLabel = 'audit';

    protected static string|UnitEnum|null $navigationGroup = 'Bezpečnosť';

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('created_at')->label('Kedy')->dateTime('d.m.Y H:i:s'),
            TextEntry::make('causer.email')->label('Kto')->placeholder('systém'),
            TextEntry::make('description')->label('Čo'),
            TextEntry::make('subject_type')->label('Objekt')
                ->formatStateUsing(fn (?string $state, Activity $record): string => class_basename($state ?? '').' #'.$record->subject_id),
            TextEntry::make('properties')->label('Detaily')->fontFamily('mono')->columnSpanFull()
                ->formatStateUsing(fn ($state): string => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with('causer'))
            ->columns([
                TextColumn::make('created_at')->label('Kedy')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('log_name')->label('Zdroj')->badge()
                    ->color(fn (?string $state): string => $state === PlatformAudit::LOG ? 'warning' : 'gray'),
                TextColumn::make('causer.email')->label('Kto')->placeholder('systém')->searchable(),
                TextColumn::make('event')->label('Udalosť')->searchable(),
                TextColumn::make('subject_type')->label('Objekt')
                    ->formatStateUsing(fn (?string $state, Activity $record): string => class_basename($state ?? '').' #'.$record->subject_id),
            ])
            ->filters([
                SelectFilter::make('log_name')->label('Zdroj')
                    ->options(fn (): array => Activity::query()->distinct()->pluck('log_name', 'log_name')->filter()->all()),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivities::route('/'),
        ];
    }
}
