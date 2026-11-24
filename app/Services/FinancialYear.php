<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class FinancialYear
{
    /**
     * Determine the Indian Financial Year string (April 1 to March 31) for a given date.
     * Returns "YYYY-YY" format, e.g. "2026-27".
     *
     * Examples:
     * - Jan 15, 2027 => "2026-27"
     * - April 1, 2026 => "2026-27"
     * - March 31, 2026 => "2025-26"
     */
    public static function forDate(CarbonInterface|string|null $date = null): string
    {
        $d = $date instanceof CarbonInterface ? $date : Carbon::parse($date ?? now());

        $year = (int) $d->year;
        $month = (int) $d->month;

        // If month is Jan, Feb, or Mar (1, 2, 3), FY started in previous calendar year
        if ($month < 4) {
            $startYear = $year - 1;
            $endYear = $year;
        } else {
            // April through December (4 to 12)
            $startYear = $year;
            $endYear = $year + 1;
        }

        $endYearShort = substr((string) $endYear, -2);

        return "{$startYear}-{$endYearShort}";
    }
}
