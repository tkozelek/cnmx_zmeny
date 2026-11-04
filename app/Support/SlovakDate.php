<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * The one way dates are written in the UI: "5. 11. 2026", "5. 11.", "29. 10. – 4. 11. 2026",
 * "4. 11. 2026 8:27". A string is read as a date ("2026-11-05").
 */
final class SlovakDate
{
    public static function full(CarbonInterface|string $date): string
    {
        return self::parse($date)->format('j. n. Y');
    }

    public static function short(CarbonInterface|string $date): string
    {
        return self::parse($date)->format('j. n.');
    }

    public static function dateTime(CarbonInterface|string $date): string
    {
        return self::parse($date)->format('j. n. Y G:i');
    }

    public static function time(CarbonInterface|string $date): string
    {
        return self::parse($date)->format('G:i');
    }

    /** The year is written once, unless the range crosses New Year. */
    public static function range(CarbonInterface|string $from, CarbonInterface|string $to): string
    {
        $from = self::parse($from);
        $to = self::parse($to);

        return ($from->year === $to->year ? self::short($from) : self::full($from)).' – '.self::full($to);
    }

    public static function dayName(CarbonInterface|string $date): string
    {
        return mb_convert_case(self::parse($date)->locale('sk')->dayName, MB_CASE_TITLE);
    }

    /** "pi 6. 11." */
    public static function shortWithDay(CarbonInterface|string $date): string
    {
        return self::parse($date)->locale('sk')->minDayName.' '.self::short($date);
    }

    private static function parse(CarbonInterface|string $date): CarbonInterface
    {
        return is_string($date) ? CarbonImmutable::parse($date) : $date;
    }
}
