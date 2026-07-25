<?php

namespace App\Enums;

/**
 * The Spatie roles this application knows about.
 *
 * The rows are global (roles.team_id null) but assignments are per team, so the same
 * user can be an Admin in one cinema and an Employee in another.
 */
enum Role: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Employee = 'employee';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrátor',
            self::Manager => 'Vedúci',
            self::Employee => 'Brigádnik',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
