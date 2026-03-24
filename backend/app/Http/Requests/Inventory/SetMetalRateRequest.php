<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

/**
 * SetMetalRateRequest — validates daily metal rate updates.
 * Rates are stored per metal type and date for accurate jewellery pricing.
 */
class SetMetalRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'metal_type'    => 'required|in:GOLD_22K,GOLD_18K,GOLD_14K,SILVER,PLATINUM',
            'rate_per_gram' => 'required|numeric|min:1',
            'rate_date'     => 'required|date',
        ];
    }
}
