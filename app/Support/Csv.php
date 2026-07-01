<?php

namespace App\Support;

/**
 * Csv
 *
 * Neutralizes spreadsheet formula injection (CSV/XLSX). A cell whose value starts
 * with one of = + - @ (or a tab/carriage-return that a spreadsheet may strip back
 * to one of those) is executed as a formula when an admin opens the export in
 * Excel/LibreOffice — e.g. `=cmd|'/C calc'!A1`. Prefixing a single quote forces the
 * cell to be treated as literal text. See OWASP "CSV Injection".
 */
class Csv
{
    /** Leading characters that make a spreadsheet treat the cell as a formula. */
    private const DANGEROUS = ['=', '+', '-', '@', "\t", "\r"];

    /**
     * Return the value safe for writing into a spreadsheet cell.
     */
    public static function neutralize(mixed $value): string
    {
        $value = (string) $value;

        if ($value !== '' && in_array($value[0], self::DANGEROUS, true)) {
            return "'".$value;
        }

        return $value;
    }
}
