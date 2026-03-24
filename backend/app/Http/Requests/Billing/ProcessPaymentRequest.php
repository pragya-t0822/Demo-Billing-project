<?php

declare(strict_types=1);

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ProcessPaymentRequest — validates payment recording against a confirmed invoice.
 */
class ProcessPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_mode'           => 'required|in:CASH,CARD,UPI,NETBANKING,CHEQUE,CREDIT',
            'amount_paid'            => 'required|numeric|min:0.01',
            'payment_date'           => 'nullable|date',
            'gateway_transaction_id' => 'nullable|string',
            'cheque_number'          => 'nullable|string',
            'bank_reference'         => 'nullable|string',
        ];
    }
}
