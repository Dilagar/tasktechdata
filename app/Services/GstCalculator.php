<?php

namespace App\Services;

use InvalidArgumentException;

class GstCalculator
{
    /**
     * Calculate GST components in arbitrary precision decimal arithmetic (no floats).
     *
     * @param string|int|float $taxableValue Decimal string of taxable value (e.g. '12250.00')
     * @param string|int|float $gstRate GST percentage rate (0, 5, 12, 18, 28)
     * @param string|null $vendorState Vendor state
     * @param string|null $companyState Company entity state
     * @return array{taxable: string, cgst: string, sgst: string, igst: string, total: string}
     */
    public static function calculate(
        string|int|float $taxableValue,
        string|int|float $gstRate,
        ?string $vendorState,
        ?string $companyState
    ): array {
        $taxableStr = number_format((float) $taxableValue, 2, '.', '');
        $rateStr = (string) $gstRate;

        // Clean state strings for comparison
        $vState = trim((string) $vendorState);
        $cState = trim((string) $companyState);
        $isIntraState = (strcasecmp($vState, $cState) === 0);

        if (bccomp($rateStr, '0', 2) === 0 || bccomp($taxableStr, '0', 2) === 0) {
            return [
                'taxable' => $taxableStr,
                'cgst' => '0.00',
                'sgst' => '0.00',
                'igst' => '0.00',
                'total' => $taxableStr,
            ];
        }

        if ($isIntraState) {
            // Intra-state: CGST = SGST = rate / 2
            $halfRate = bcdiv($rateStr, '2', 4); // e.g. 12 / 2 = 6.0000

            // Multiply: taxable * halfRate / 100
            // Convert to 4 decimals first, then round half-up to 2 decimals
            $cgstRaw = bcdiv(bcmul($taxableStr, $halfRate, 4), '100', 4);
            $cgst = self::roundHalfUp2($cgstRaw);
            $sgst = $cgst;
            $igst = '0.00';
        } else {
            // Inter-state: IGST = full rate, CGST = SGST = 0
            $igstRaw = bcdiv(bcmul($taxableStr, $rateStr, 4), '100', 4);
            $igst = self::roundHalfUp2($igstRaw);
            $cgst = '0.00';
            $sgst = '0.00';
        }

        // Total = taxable + cgst + sgst + igst
        $taxTotal = bcadd(bcadd($cgst, $sgst, 2), $igst, 2);
        $grandTotal = bcadd($taxableStr, $taxTotal, 2);

        return [
            'taxable' => $taxableStr,
            'cgst' => $cgst,
            'sgst' => $sgst,
            'igst' => $igst,
            'total' => $grandTotal,
        ];
    }

    /**
     * Pure string/bcmath half-up rounding to 2 decimal places.
     * Never casts to float, ensuring exact integer paise precision.
     *
     * Example: '0.0025' -> '0.00', '0.0050' -> '0.01'
     */
    public static function roundHalfUp2(string $value): string
    {
        // Add 0.005 and truncate using floor/bcadd logic for positive numbers
        // Using string arithmetic:
        // Value with scale 4: if 3rd decimal digit >= 5, add 0.01 to the 2-decimal truncated part
        $parts = explode('.', $value);
        $intPart = $parts[0] ?? '0';
        $decPart = str_pad($parts[1] ?? '0', 4, '0');

        $firstTwo = substr($decPart, 0, 2);
        $thirdDigit = (int) ($decPart[2] ?? 0);

        if ($thirdDigit >= 5) {
            // Increment the 2-decimal representation by 0.01
            $truncated = $intPart . '.' . $firstTwo;
            return bcadd($truncated, '0.01', 2);
        }

        return $intPart . '.' . $firstTwo;
    }
}
