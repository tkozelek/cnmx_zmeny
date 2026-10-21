<?php

namespace App\Filament\Platform\Resources\Teams\RelationManagers;

use App\Enums\TeamFeature;
use App\Models\PositionGroup;
use App\Models\Team;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;

/** No delete: positions cascade into assignments, so a retired one is deactivated instead. */
class PositionsRelationManager extends RelationManager
{
    protected static string $relationship = 'positions';

    protected static ?string $title = 'Pozície';

    protected static ?string $modelLabel = 'pozícia';

    protected static ?string $pluralModelLabel = 'pozície';

    protected static bool $shouldSkipAuthorization = true;

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof Team && $ownerRecord->hasFeature(TeamFeature::Rozpis);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->label('Názov')->required()->maxLength(80)
                ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule) => $rule->where('team_id', $this->getOwnerRecord()->getKey())),
            TextInput::make('code')->label('Skratka')->maxLength(10),
            Select::make('position_group_id')
                ->label('Skupina')
                ->options(fn (): array => PositionGroup::withoutGlobalScope('team')
                    ->where('team_id', $this->getOwnerRecord()->getKey())
                    ->orderBy('sort_order')
                    ->pluck('name', 'id')
                    ->all()),
            ColorPicker::make('color')->label('Farba')->regex('/^#[0-9a-fA-F]{6}$/'),
            TextInput::make('sort_order')->label('Poradie')->integer()->minValue(0)->default(0),
            Toggle::make('is_manager')->label('Vedúca pozícia'),
            Toggle::make('is_active')->label('Aktívna')->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                ColorColumn::make('color')->label(''),
                TextColumn::make('name')->label('Názov')->searchable(),
                TextColumn::make('code')->label('Skratka'),
                TextColumn::make('group.name')->label('Skupina')->placeholder('—'),
                IconColumn::make('is_manager')->label('Vedúca')->boolean(),
                IconColumn::make('is_active')->label('Aktívna')->boolean(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
