<?php

declare(strict_types=1);

namespace App\Http\Requests\Accounting;

use Illuminate\Foundation\Http\FormRequest;

/**
 * PostJournalRequest — validates manual journal entry creation.
 * Enforces minimum 2 lines (double-entry requirement) and valid account codes.
 * Business-layer balance check (DR = CR) is enforced in JournalService.
 */
class PostJournalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fiscal_period_id'     => 'required|uuid|exists:fiscal_periods,id',
            'store_id'             => 'required|uuid|exists:stores,id',
            'entry_date'           => 'required|date',
            'reference_type'       => 'required|in:INVOICE,PAYMENT,SETTLEMENT,ADJUSTMENT,REVERSAL,OPENING,CLOSING,DEPRECIATION,GST_SETTLEMENT',
            'reference_id'         => 'nullable|string',
            'narration'            => 'required|string|max:255',
            'lines'                => 'required|array|min:2',
            'lines.*.account_code' => 'required|string|exists:chart_of_accounts,code',
            'lines.*.debit_amount' => 'nullable|numeric|min:0',
            'lines.*.credit_amount'=> 'nullable|numeric|min:0',
            'lines.*.description'  => 'nullable|string',
        ];
    }
}
