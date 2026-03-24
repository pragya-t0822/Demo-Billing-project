<?php

declare(strict_types=1);

namespace App\Http\Requests\Accounting;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ReverseJournalRequest — validates the mandatory reason for a journal reversal.
 * Reversals are the only permitted correction for posted journal entries.
 */
class ReverseJournalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => 'required|string|min:10',
        ];
    }
}
