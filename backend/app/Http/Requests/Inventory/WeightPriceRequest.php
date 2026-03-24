<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

/**
 * WeightPriceRequest — validates inputs for weight-based jewellery pricing calculations.
 * Covers gross/net weight, making charges, wastage, hallmark charges, and GST rate.
 */
class WeightPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'metal_type'          => 'required|in:GOLD_22K,GOLD_18K,GOLD_14K,SILVER,PLATINUM',
            'gross_weight_grams'  => 'required|numeric|min:0.001',
            'net_weight_grams'    => 'required|numeric|min:0.001',
            'making_charges'      => 'required|numeric|min:0',
            'making_charges_type' => 'required|in:FLAT,PERCENTAGE',
            'wastage_percentage'  => 'nullable|numeric|min:0|max:15',
            'hallmark_charge'     => 'nullable|numeric|min:0',
            'gst_rate'            => 'nullable|numeric|in:3,5',
        ];
    }
}
