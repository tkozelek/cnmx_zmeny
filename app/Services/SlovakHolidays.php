<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Slovak statutory public holidays (štátne sviatky a dni pracovného pokoja).
 *
 * Feeds FairnessService::dayWeight(): a holiday is priced by the team's configured
 * holiday_weight regardless of which weekday it falls on that year, instead of its usual
 * weekday weight. No ext-calendar dependency (not declared in composer.json, and not
 * guaranteed enabled in production) - Easter Sunday is computed with the standard Anonymous
 * Gregorian algorithm instead of PHP's easter_date().
 */
class SlovakHolidays
{
    /**
     * [month, day] pairs that fall on the same date every year.
     *
     * @var list<array{0: int, 1: int}>
     */
    private const array FIXED = [
        [1, 1],   // Deň vzniku Slovenskej republiky
        [1, 6],   // Zjavenie Pána (Traja králi)
        [5, 1],   // Sviatok práce
        [5, 8],   // Deň víťazstva nad fašizmom
        [7, 5],   // Sviatok svätého Cyrila a svätého Metoda
        [8, 29],  // Výročie Slovenského národného povstania
        [9, 1],   // Deň Ústavy Slovenskej republiky
        [9, 15],  // Sedembolestná Panna Mária
        [11, 1],  // Sviatok všetkých svätých
        [11, 17], // Deň boja za slobodu a demokraciu
        [12, 24], // Štedrý deň
        [12, 25], // Prvý sviatok vianočný
        [12, 26], // Druhý sviatok vianočný
    ];

    public static function isHoliday(CarbonInterface $date): bool
    {
        foreach (self::FIXED as [$month, $day]) {
            if ($date->month === $month && $date->day === $day) {
                return true;
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
