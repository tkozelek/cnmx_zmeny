<?php

namespace App\Filament\Platform\Concerns;

use App\Enums\Role;
use App\Services\SuperAdminSession;
use App\Services\TeamMembershipService;
use DomainException;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;

trait ManagesMemberships
{
    private function roleSelect(): Select
    {
        return Select::make('role')
            ->label('Rola')
            ->options(collect(Role::cases())->mapWithKeys(fn (Role $role): array => [$role->value => $role->label()])->all())
            ->default(Role::Employee->value)
            ->required()
            ->live();
    }

    /** Handing someone a whole cinema asks for the acting admin's password, as granting super admin does. */
    private function headManagerPassword(): TextInput
    {
        return TextInput::make('current_password')
            ->label('Tvoje heslo')
            ->helperText('Hlavný manažér spravuje celé kino – potvrď to svojím heslom.')
            ->password()
            ->visible(fn (Get $get): bool => $get('role') === Role::HeadManager->value)
            ->required()
            ->currentPassword(guard: SuperAdminSession::GUARD);
    }

    private function attempt(callable $change): void
    {
        try {
            $change();
        } catch (DomainException $e) {
            Notification::make()->danger()->title($e->getMessage())->send();
        }
    }

    private function membership(): TeamMembershipService
    {
        return app(TeamMembershipService::class);
    }
}
