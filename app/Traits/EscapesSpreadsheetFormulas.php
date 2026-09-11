<?php

namespace App\Traits;

/**
 * Stops a person's name being run as a formula when the exported sheet is opened.
 *
 * Names reach these files from registration, where they are only length-checked, and
 * `User::__toString()` puts the surname first - so a surname of `=HYPERLINK("http://evil",...)`
 * or `=cmd|'/c calc'!A0` lands at the start of a cell, which is all Excel needs to treat it as a
 * formula rather than text. The person who opens the file is a manager.
 *
 * A leading apostrophe is Excel's own "this is literal text" marker. Applied at the string level
 * rather than via setCellValueExplicit() because the grids go in through fromArray(), which
 * infers the cell type per value and gives no place to declare one.
 */
trait EscapesSpreadsheetFormulas
{
    /** The characters Excel reads as "a formula starts here". */
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
