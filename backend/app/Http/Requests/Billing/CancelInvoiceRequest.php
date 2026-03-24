<?php

declare(strict_types=1);

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CancelInvoiceRequest — validates the mandatory cancellation reason.
 * A reason is required to satisfy audit trail requirements.
 */
class CancelInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => 'required|string|min:5',
        ];
    }
}
