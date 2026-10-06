<?php

namespace App\Enums;

/**
 * The Spatie roles this application knows about.
 *
 * The rows are global (roles.team_id null) but assignments are per team, so the same
 * user can be a HeadManager in one cinema and an Employee in another.
 */
enum Role: string
{
    case HeadManager = 'head-manager';
    case Manager = 'manager';
    case Employee = 'employee';

    public function label(): string
    {
        return match ($this) {
            self::HeadManager => 'Hlavný manažér',
            self::Manager => 'Manažér',
            self::Employee => 'Brigádnik',
        };
    }

    /** Badge colours, the same on the users table and the profile card. */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::HeadManager, self::Manager => 'bg-brand-500/10 text-brand-300 border-brand-500/30',
            self::Employee => 'bg-neutral-800 text-neutral-300 border-neutral-700',
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
