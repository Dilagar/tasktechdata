<?php

namespace Tests\Unit;

use App\Services\FinancialYear;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class FinancialYearTest extends TestCase
{
    /**
     * Jan 2027 => "2026-27"
     * April 2026 => "2026-27"
     * March 2026 => "2025-26"
     */
    public function test_financial_year_three_required_dates(): void
    {
        $jan2027 = FinancialYear::forDate(Carbon::create(2027, 1, 15));
        $this->assertSame('2026-27', $jan2027);

        $apr2026 = FinancialYear::forDate(Carbon::create(2026, 4, 1));
        $this->assertSame('2026-27', $apr2026);

        $mar2026 = FinancialYear::forDate(Carbon::create(2026, 3, 31));
        $this->assertSame('2025-26', $mar2026);
    }
}
