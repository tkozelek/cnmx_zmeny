<?php

namespace App\Filament\Platform\Resources\BugReports;

use App\Filament\Platform\Resources\BugReports\Pages\ListBugReports;
use App\Filament\Platform\Resources\BugReports\Pages\ViewBugReport;
use App\Models\BugReport;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Number;
use UnitEnum;

class BugReportResource extends Resource
{
    protected static ?string $model = BugReport::class;

    protected static bool $shouldSkipAuthorization = true;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBugAnt;

    protected static ?string $modelLabel = 'nahlásená chyba';

    protected static ?string $pluralModelLabel = 'nahlásené chyby';

    protected static string|UnitEnum|null $navigationGroup = 'Prevádzka';

    public static function getNavigationBadge(): ?string
    {
        $open = BugReport::query()->whereNull('resolved_at')->count();

        return $open > 0 ? (string) $open : null;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('created_at')->label('Odoslané')->dateTime('d.m.Y H:i'),
            TextEntry::make('resolved_at')->label('Vyriešené')->dateTime('d.m.Y H:i')->placeholder('nie'),
            TextEntry::make('email')->label('E-mail na odpoveď')->copyable(),
            TextEntry::make('user_id')->label('Účet')
                ->formatStateUsing(fn (?int $state): string => '#'.$state)
                ->placeholder('hosť (bez prihlásenia)'),
            TextEntry::make('team.name')->label('Kino')->placeholder('–'),
            TextEntry::make('page')->label('Stránka')->placeholder('–'),
            TextEntry::make('user_agent')->label('Prehliadač')->placeholder('–')->columnSpanFull(),
            TextEntry::make('description')->label('Popis')->columnSpanFull()
                ->formatStateUsing(fn (string $state): HtmlString => new HtmlString(nl2br(e($state)))),
            TextEntry::make('attachments')->label('Prílohy')->columnSpanFull()
                ->state(fn (BugReport $record): array => array_map(
                    fn (array $attachment): string => $attachment['name'].' ('.Number::fileSize($attachment['size'], precision: 1).')',
                    $record->attachments,
                ))
                ->listWithLineBreaks()
                ->helperText('Stiahneš ich tlačidlami hore.')
                ->placeholder('žiadne'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('team'))
            ->columns([
                TextColumn::make('created_at')->label('Odoslané')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('email')->label('Od')->searchable()
                    ->description(fn (BugReport $record): string => $record->user_id ? 'účet #'.$record->user_id : 'hosť'),
                TextColumn::make('team.name')->label('Kino')->placeholder('–'),
                TextColumn::make('description')->label('Popis')->limit(60)->searchable(),
                TextColumn::make('attachments')->label('Prílohy')
                    ->state(fn (BugReport $record): int => count($record->attachments)),
                IconColumn::make('resolved_at')->label('Vyriešené')->boolean()
                    ->state(fn (BugReport $record): bool => $record->resolved_at !== null),
            ])
            ->filters([
                TernaryFilter::make('resolved_at')->label('Vyriešené')->nullable()->default(false),
            ])
            ->recordActions([
                ViewAction::make(),
                self::toggleResolvedAction(),
                DeleteAction::make(),
            ]);
    }

    public static function toggleResolvedAction(): Action
    {
        return Action::make('toggleResolved')
            ->label(fn (BugReport $record): string => $record->resolved_at ? 'Znovu otvoriť' : 'Vyriešené')
            ->icon(fn (BugReport $record): Heroicon => $record->resolved_at ? Heroicon::OutlinedArrowUturnLeft : Heroicon::OutlinedCheckCircle)
            ->color(fn (BugReport $record): string => $record->resolved_at ? 'gray' : 'success')
            ->action(fn (BugReport $record) => $record->update(['resolved_at' => $record->resolved_at ? null : now()]));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBugReports::route('/'),
            'view' => ViewBugReport::route('/{record}'),
        ];
    }
}
