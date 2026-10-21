<?php

namespace App\Filament\Platform\Resources\Permissions;

use App\Enums\Role;
use App\Filament\Platform\Resources\Permissions\Pages\ListPermissions;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;
use UnitEnum;

/** Read-only: the matrix is code (RolePermissionSeeder), deployed with `php artisan permissions:sync`. */
class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;

    protected static bool $shouldSkipAuthorization = true;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLockClosed;

    protected static ?string $modelLabel = 'oprávnenie';

    protected static ?string $pluralModelLabel = 'roly a oprávnenia';

    protected static string|UnitEnum|null $navigationGroup = 'Bezpečnosť';

    public static function table(Table $table): Table
    {
        $grants = SpatieRole::with('permissions')->get()
            ->mapWithKeys(fn (SpatieRole $role): array => [$role->name => $role->permissions->pluck('name')->all()]);

        return $table
            ->description('Matica je definovaná v kóde (RolePermissionSeeder) a nasadzuje sa cez php artisan permissions:sync. Super admin má všetko v každom kine.')
            ->defaultSort('name')
            ->paginated(false)
            ->columns([
                TextColumn::make('name')->label('Oprávnenie')->searchable()->fontFamily('mono'),
                ...array_map(
                    fn (Role $role): IconColumn => IconColumn::make("role_{$role->value}")
                        ->label($role->label())
                        ->boolean()
                        ->state(fn (Permission $record): bool => in_array($record->name, $grants[$role->value] ?? [], true)),
                    Role::cases(),
                ),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPermissions::route('/'),
        ];
    }
}
