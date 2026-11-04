<?php

namespace App\Support;

/** "1 deň", "3 dni", "5 dní" - the count plus the right one of the three Slovak forms. */
final class SlovakPlural
{
    public static function of(int $count, string $one, string $few, string $many): string
    {
        $word = match (true) {
            $count === 1 => $one,
            $count >= 2 && $count <= 4 => $few,
            default => $many,
        };

        return $count.' '.$word;
    }
}
