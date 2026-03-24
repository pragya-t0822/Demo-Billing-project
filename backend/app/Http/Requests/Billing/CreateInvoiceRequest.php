<?php

declare(strict_types=1);

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CreateInvoiceRequest — validates incoming invoice creation payload.
 * Covers both SKU-based and weight-based line items.
 */
class CreateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'store_id'                     => 'required|uuid|exists:stores,id',
            'customer_id'                  => 'nullable|uuid|exists:customers,id',
            'due_date'                     => 'nullable|date',
            'payment_mode'                 => 'nullable|in:CASH,CARD,UPI,NETBANKING,CHEQUE,CREDIT',
            'notes'                        => 'nullable|string|max:500',
            'line_items'                   => 'required|array|min:1',
            'line_items.*.product_id'      => 'required|uuid|exists:products,id',
            'line_items.*.product_name'    => 'required|string',
            'line_items.*.hsn_code'        => 'required|string',
            'line_items.*.base_price'      => 'required|numeric|min:0',
            'line_items.*.gst_rate'        => 'required|numeric|min:0',
            'line_items.*.quantity'        => 'nullable|numeric|min:0.001',
            'line_items.*.discount_amount' => 'nullable|numeric|min:0',
        ];
    }
}
