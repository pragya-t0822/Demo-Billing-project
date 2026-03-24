<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Exceptions\BusinessRuleException;

/**
 * GstService — computes correct GST split per line item.
 *
 * Rules:
 *  - INTRA-state: CGST = gst_rate/2, SGST = gst_rate/2, IGST = 0
 *  - INTER-state: IGST = gst_rate, CGST = 0, SGST = 0
 *  - Discount applied BEFORE GST on the net taxable amount
 *  - Rounded to 2 decimal places (standard rounding)
 */
class GstService
{
    // Standard GST rates in India
    const VALID_RATES = [0, 0.25, 1, 1.5, 3, 5, 7.5, 12, 18, 28];

    public function calculate(
        float $taxableAmount,
        float $gstRate,
        string $supplyType, // INTRA or INTER
        string $hsnCode
    ): array {
        if (! in_array($gstRate, self::VALID_RATES, strict: false)) {
            throw new BusinessRuleException(
                "Invalid GST rate {$gstRate}% for HSN {$hsnCode}. Valid rates: " . implode(', ', self::VALID_RATES),
                'INVALID_GST_RATE'
            );
        }

        if ($supplyType === 'INTRA') {
            $halfRate   = $gstRate / 2;
            $cgstRate   = $halfRate;
            $sgstRate   = $halfRate;
            $igstRate   = 0;
            $cgstAmount = round(($taxableAmount * $cgstRate) / 100, 2);
            $sgstAmount = round(($taxableAmount * $sgstRate) / 100, 2);
            $igstAmount = 0;
        } else {
            $cgstRate   = 0;
            $sgstRate   = 0;
            $igstRate   = $gstRate;
            $cgstAmount = 0;
            $sgstAmount = 0;
            $igstAmount = round(($taxableAmount * $igstRate) / 100, 2);
        }

        $totalTax       = $cgstAmount + $sgstAmount + $igstAmount;
        $totalWithTax   = round($taxableAmount + $totalTax, 2);

        return [
            'cgst_rate'     => $cgstRate,
            'sgst_rate'     => $sgstRate,
            'igst_rate'     => $igstRate,
            'cgst_amount'   => $cgstAmount,
            'sgst_amount'   => $sgstAmount,
            'igst_amount'   => $igstAmount,
            'total_tax'     => $totalTax,
            'total_with_tax'=> $totalWithTax,
        ];
    }
}
