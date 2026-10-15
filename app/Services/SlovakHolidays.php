<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Slovak statutory public holidays (štátne sviatky a dni pracovného pokoja).
 *
 * Flags a day card as a sviatok. No ext-calendar dependency (not declared in composer.json, and not
 * guaranteed enabled in production) - Easter Sunday is computed with the standard Anonymous
 * Gregorian algorithm instead of PHP's easter_date().
 */
class SlovakHolidays
{
    /**
     * [month, day, is-a-working-day-in(year)] for the dates that sit on the same day every year.
     *
     * The 2024/2025 consolidation laws took 1.9 and 17.11 off the list from 2025 on, and
     * 8.5 and 15.9 for 2026 only - they stay state holidays, but people work.
     *
     * @return list<array{0: int, 1: int, 2: callable(int): bool}>
     */
    private static function fixed(): array
    {
        $always = fn (int $year): bool => false;

        return [
            [1, 1, $always],   // Deň vzniku Slovenskej republiky
            [1, 6, $always],   // Zjavenie Pána (Traja králi)
            [5, 1, $always],   // Sviatok práce
            [5, 8, fn (int $year): bool => $year === 2026],   // Deň víťazstva nad fašizmom
            [7, 5, $always],   // Sviatok svätého Cyrila a svätého Metoda
            [8, 29, $always],  // Výročie Slovenského národného povstania
            [9, 1, fn (int $year): bool => $year >= 2025],    // Deň Ústavy Slovenskej republiky
            [9, 15, fn (int $year): bool => $year === 2026],  // Sedembolestná Panna Mária
            [11, 1, $always],  // Sviatok všetkých svätých
            [11, 17, fn (int $year): bool => $year >= 2025],  // Deň boja za slobodu a demokraciu
            [12, 24, $always], // Štedrý deň
            [12, 25, $always], // Prvý sviatok vianočný
            [12, 26, $always], // Druhý sviatok vianočný
        ];
    }

    public static function isHoliday(CarbonInterface $date): bool
    {
        foreach (self::fixed() as [$month, $day, $workingDayIn]) {
            if ($date->month === $month && $date->day === $day) {
                return ! $workingDayIn($date->year);
            }
        }

        $easter = self::easterSunday($date->year);

        // Veľký piatok (Good Friday) and Veľkonočný pondelok (Easter Monday) - the only two
        // Slovak public holidays that move with Easter rather than sitting on a fixed date.
        return $date->isSameDay($easter->subDays(2)) || $date->isSameDay($easter->addDay());
    }

    /** Easter Sunday via the Anonymous Gregorian algorithm (Meeus/Jones/Butcher). */
    private static function easterSunday(int $year): CarbonImmutable
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $monthDay = $h + $l - 7 * $m + 114;

        return CarbonImmutable::create($year, intdiv($monthDay, 31), ($monthDay % 31) + 1);
    }
}
