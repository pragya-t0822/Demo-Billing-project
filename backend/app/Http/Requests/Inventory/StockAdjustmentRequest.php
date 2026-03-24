<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

/**
 * StockAdjustmentRequest — validates manual stock adjustment inputs.
 * Adjustment value must be non-zero; negative values decrease stock.
 */
class StockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|uuid|exists:products,id',
            'store_id'   => 'required|uuid|exists:stores,id',
            'adjustment' => 'required|numeric|not_in:0',
            'reason'     => 'required|string|min:5',
        ];
    }
}
