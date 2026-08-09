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

    /**
     * Everyone who outranks a brigádnik, most senior first.
     *
     * This is who the vedúci slot may be filled from. Deliberately a role question rather than a
     * signup question: a manažér running Friday night does not write themselves into the pool the
     * way a brigádnik does - they are simply on.
     *
     * @return list<self>
     */
    public static function leadership(): array
    {
        return [self::HeadManager, self::Manager];
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
