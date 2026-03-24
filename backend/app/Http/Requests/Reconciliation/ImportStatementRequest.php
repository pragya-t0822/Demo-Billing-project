<?php

declare(strict_types=1);

namespace App\Http\Requests\Reconciliation;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ImportStatementRequest — validates bank statement import payload.
 * Requires at least one bank entry; opening and closing balances are used for variance check.
 */
class ImportStatementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'store_id'                   => 'required|uuid|exists:stores,id',
            'bank_name'                  => 'required|string',
            'account_number_masked'      => 'required|string|max:20',
            'statement_date'             => 'required|date',
            'opening_balance'            => 'required|numeric',
            'closing_balance'            => 'required|numeric',
            'entries'                    => 'required|array|min:1',
            'entries.*.entry_date'       => 'required|date',
            'entries.*.narration'        => 'required|string',
            'entries.*.credit_amount'    => 'nullable|numeric|min:0',
            'entries.*.debit_amount'     => 'nullable|numeric|min:0',
            'entries.*.reference_number' => 'nullable|string',
        ];
    }
}
