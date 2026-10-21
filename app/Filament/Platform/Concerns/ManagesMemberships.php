<?php

namespace App\Filament\Platform\Concerns;

use App\Enums\Role;
use App\Services\TeamMembershipService;
use DomainException;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;

trait ManagesMemberships
{
    private function roleSelect(): Select
    {
        return Select::make('role')
            ->label('Rola')
            ->options(collect(Role::cases())->mapWithKeys(fn (Role $role): array => [$role->value => $role->label()])->all())
            ->default(Role::Employee->value)
            ->required();
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
