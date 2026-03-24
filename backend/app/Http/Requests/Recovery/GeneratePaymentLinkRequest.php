<?php

declare(strict_types=1);

namespace App\Http\Requests\Recovery;

use Illuminate\Foundation\Http\FormRequest;

/**
 * GeneratePaymentLinkRequest — validates payment link generation for overdue invoices.
 * Expiry window is capped at 168 hours (7 days) to limit exposure.
 */
class GeneratePaymentLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'invoice_id'   => 'required|uuid|exists:invoices,id',
            'customer_id'  => 'nullable|uuid|exists:customers,id',
            'amount'       => 'required|numeric|min:0.01',
            'expiry_hours' => 'nullable|integer|min:1|max:168',
        ];
    }
}
