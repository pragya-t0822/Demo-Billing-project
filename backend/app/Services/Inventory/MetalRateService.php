<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Exceptions\BusinessRuleException;
use App\Models\MetalRate;
use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;

/**
 * MetalRateService — calculates final sale price for weight-based jewellery.
 *
 * Rules:
 *  - Live rate must be fetched; reject if older than 15 minutes
 *  - Wastage percentage must be 0–15%
 *  - All weights with 3 decimal precision
 *  - Record rate_timestamp on every invoice line item
 */
class MetalRateService
{
    const MAX_WASTAGE_PERCENTAGE = 15;
    const RATE_STALENESS_MINUTES = 15;

    public function __construct(
        private readonly AuditService $audit,
    ) {}

    /**
     * Persist a new metal rate and write an audit entry.
     */
    public function setRate(string $metalType, float $ratePerGram, string $rateDate): MetalRate
    {
        if ($ratePerGram <= 0) {
            throw new BusinessRuleException(
                "Rate per gram must be greater than zero. Got: {$ratePerGram}",
                'INVALID_METAL_RATE'
            );
        }

        /** @var MetalRate $rate */
        $rate = MetalRate::create([
            'metal_type'    => $metalType,
            'rate_per_gram' => $ratePerGram,
            'rate_date'     => $rateDate,
            'source'        => 'MANUAL',
            'set_by'        => Auth::id(),
        ]);

        $this->audit->log('SET_METAL_RATE', 'MetalRate', $rate->id,
            null,
            ['metal_type' => $metalType, 'rate_per_gram' => $ratePerGram, 'rate_date' => $rateDate]
        );

        return $rate;
    }

    /**
     * Calculate the total price for a weight-based jewellery item.
     */
    public function calculateWeightBasedPrice(
        string $metalType,
        float $grossWeightGrams,
        float $netWeightGrams,
        float $makingCharges,
        string $makingChargesType, // FLAT or PERCENTAGE
        float $wastagePercentage = 0,
        float $hallmarkCharge = 0,
        float $gstRate = 3
    ): array {
        if ($wastagePercentage > self::MAX_WASTAGE_PERCENTAGE) {
            throw new BusinessRuleException(
                "Wastage percentage {$wastagePercentage}% exceeds maximum allowed " . self::MAX_WASTAGE_PERCENTAGE . "%.",
                'INVALID_WASTAGE_PERCENTAGE'
            );
        }

        // Fetch latest live rate (reject if stale)
        $metalRate = $this->getLiveRate($metalType);

        $ratePerGram = (float) $metalRate->rate_per_gram;

        // Metal value = net weight × rate
        $metalValue = round($netWeightGrams * $ratePerGram, 2);

        // Wastage = gross_weight × wastage% × rate
        $wastageAmount = round(($grossWeightGrams * ($wastagePercentage / 100)) * $ratePerGram, 2);

        // Making charges
        $makingChargesAmount = $makingChargesType === 'PERCENTAGE'
            ? round(($metalValue * $makingCharges) / 100, 2)
            : round($makingCharges, 2);

        $basePrice = round($metalValue + $wastageAmount + $makingChargesAmount + $hallmarkCharge, 2);

        // GST on base price (3% for gold jewellery)
        $gstAmount  = round(($basePrice * $gstRate) / 100, 2);
        $totalPrice = round($basePrice + $gstAmount, 2);

        return [
            'metal_type'            => $metalType,
            'live_rate_per_gram'    => $ratePerGram,
            'rate_timestamp'        => $metalRate->updated_at ?? $metalRate->created_at ?? now(),
            'gross_weight_grams'    => round($grossWeightGrams, 3),
            'net_weight_grams'      => round($netWeightGrams, 3),
            'metal_value'           => $metalValue,
            'wastage_percentage'    => $wastagePercentage,
            'wastage_amount'        => $wastageAmount,
            'making_charges'        => $makingChargesAmount,
            'hallmark_charge'       => $hallmarkCharge,
            'base_price'            => $basePrice,
            'gst_rate'              => $gstRate,
            'gst_amount'            => $gstAmount,
            'total_price'           => $totalPrice,
        ];
    }

    public function getLiveRate(string $metalType): MetalRate
    {
        $rate = MetalRate::latestFor($metalType);

        if (! $rate) {
            throw new BusinessRuleException(
                "No rate found for metal type {$metalType}. Please set the rate before billing.",
                'MISSING_METAL_RATE'
            );
        }

        // Check staleness — use updated_at, fall back to created_at; default 0 if both null
        $ageMinutes = ($rate->updated_at ?? $rate->created_at)?->diffInMinutes(now()) ?? 0;

        if ($ageMinutes > self::RATE_STALENESS_MINUTES) {
            throw new BusinessRuleException(
                "Metal rate for {$metalType} is stale ({$ageMinutes} minutes old). Refresh the rate before billing.",
                'STALE_METAL_RATE'
            );
        }

        return $rate;
    }
}
