<?php

namespace Tests\Unit;

use App\Services\GstCalculator;
use PHPUnit\Framework\TestCase;

class GstCalculatorTest extends TestCase
{
    /**
     * 12,250 at 12% intra-state => CGST 735.00, SGST 735.00, total 13,720.00
     */
    public function test_s1_case_12250_at_12_percent_intra_state(): void
    {
        $res = GstCalculator::calculate('12250.00', '12', 'Maharashtra', 'Maharashtra');

        $this->assertSame('12250.00', $res['taxable']);
        $this->assertSame('735.00', $res['cgst']);
        $this->assertSame('735.00', $res['sgst']);
        $this->assertSame('0.00', $res['igst']);
        $this->assertSame('13720.00', $res['total']);
    }

    /**
     * 1,000 at 18% intra => 90.00 + 90.00
     */
    public function test_1000_at_18_percent_intra_state(): void
    {
        $res = GstCalculator::calculate('1000.00', '18', 'Maharashtra', 'Maharashtra');

        $this->assertSame('1000.00', $res['taxable']);
        $this->assertSame('90.00', $res['cgst']);
        $this->assertSame('90.00', $res['sgst']);
        $this->assertSame('0.00', $res['igst']);
        $this->assertSame('1180.00', $res['total']);
    }

    /**
     * Inter-state 18% => IGST only
     */
    public function test_inter_state_18_percent_igst_only(): void
    {
        $res = GstCalculator::calculate('195000.00', '18', 'Karnataka', 'Maharashtra');

        $this->assertSame('195000.00', $res['taxable']);
        $this->assertSame('0.00', $res['cgst']);
        $this->assertSame('0.00', $res['sgst']);
        $this->assertSame('35100.00', $res['igst']);
        $this->assertSame('230100.00', $res['total']);
    }

    /**
     * 0% rate => zero tax
     */
    public function test_zero_percent_rate_has_zero_tax(): void
    {
        $res = GstCalculator::calculate('5000.00', '0', 'Maharashtra', 'Maharashtra');

        $this->assertSame('5000.00', $res['taxable']);
        $this->assertSame('0.00', $res['cgst']);
        $this->assertSame('0.00', $res['sgst']);
        $this->assertSame('0.00', $res['igst']);
        $this->assertSame('5000.00', $res['total']);
    }

    /**
     * Half-up case: taxable 0.05 at 5% intra-state
     * Half rate = 2.5% => 0.05 * 2.5 / 100 = 0.00125 -> 0.00
     * Taxable 0.10 at 5% inter-state => 0.10 * 5 / 100 = 0.0050 -> 0.01 (half up)
     */
    public function test_half_up_rounding_edge_case(): void
    {
        // 0.10 at 5% inter-state: exact 0.0050 => rounds up to 0.01
        $res = GstCalculator::calculate('0.10', '5', 'Karnataka', 'Maharashtra');
        $this->assertSame('0.01', $res['igst']);
        $this->assertSame('0.11', $res['total']);

        // 0.05 at 5% inter-state: exact 0.0025 => rounds down to 0.00
        $resDown = GstCalculator::calculate('0.05', '5', 'Karnataka', 'Maharashtra');
        $this->assertSame('0.00', $resDown['igst']);
        $this->assertSame('0.05', $resDown['total']);
    }
}
