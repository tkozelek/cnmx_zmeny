<?php

namespace App\Filament\Platform\Resources\Teams;

use App\Filament\Platform\Resources\Teams\Pages\CreateTeam;
use App\Filament\Platform\Resources\Teams\Pages\EditTeam;
use App\Filament\Platform\Resources\Teams\Pages\ListTeams;
use App\Filament\Platform\Resources\Teams\RelationManagers\MembersRelationManager;
use App\Filament\Platform\Resources\Teams\RelationManagers\PositionsRelationManager;
use App\Filament\Platform\Resources\Teams\RelationManagers\WeekLocksRelationManager;
use App\Filament\Platform\Resources\Teams\Schemas\TeamForm;
use App\Filament\Platform\Resources\Teams\Tables\TeamsTable;
use App\Models\Team;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TeamResource extends Resource
{
    protected static ?string $model = Team::class;

    /** The whole panel is super-admin only (User::canAccessPanel); the cinema policies assume a current team. */
    protected static bool $shouldSkipAuthorization = true;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFilm;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'kino';

    protected static ?string $pluralModelLabel = 'kiná';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return TeamForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TeamsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            MembersRelationManager::class,
            PositionsRelationManager::class,
            WeekLocksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTeams::route('/'),
            'create' => CreateTeam::route('/create'),
            'edit' => EditTeam::route('/{record}/edit'),
        ];
    }
}
