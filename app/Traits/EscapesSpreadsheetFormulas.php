<?php

namespace App\Traits;

/**
 * Prefixes values Excel would run as a formula (e.g. a surname "=cmd|...") with an apostrophe.
 */
trait EscapesSpreadsheetFormulas
{
    private const FORMULA_TRIGGERS = ['=', '+', '-', '@', "\t", "\r"];

    protected function escapeFormula(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return in_array(mb_substr($value, 0, 1), self::FORMULA_TRIGGERS, true)
            ? "'".$value
            : $value;
    }
}
