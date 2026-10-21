<?php

namespace App\Filament\Platform\Resources\Assignments;

use App\Filament\Platform\Resources\Assignments\Pages\ListAssignments;
use App\Models\Assignment;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AssignmentResource extends Resource
{
    protected static ?string $model = Assignment::class;

    protected static bool $shouldSkipAuthorization = true;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $modelLabel = 'zápis';

    protected static ?string $pluralModelLabel = 'zápisy';

    protected static string|UnitEnum|null $navigationGroup = 'Prevádzka';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['team', 'user']))
            ->columns([
                TextColumn::make('team.name')->label('Kino')->sortable(),
                TextColumn::make('date')->label('Deň')->date('D d.m.Y')->sortable(),
                TextColumn::make('user.email')->label('Používateľ')->searchable(),
                TextColumn::make('note')->label('Poznámka')->limit(40)->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('Zapísané')->dateTime('d.m.Y H:i')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('team')->label('Kino')->relationship('team', 'name'),
                Filter::make('date')
                    ->schema([
                        DatePicker::make('from')->label('Od'),
                        DatePicker::make('until')->label('Do'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, string $from) => $q->whereDate('date', '>=', $from))
                        ->when($data['until'] ?? null, fn (Builder $q, string $until) => $q->whereDate('date', '<=', $until))),
            ])
            ->recordActions([
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAssignments::route('/'),
        ];
    }
}
