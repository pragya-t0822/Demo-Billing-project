<?php

declare(strict_types=1);

namespace App\Exceptions;

/** Thrown when a journal entry's debits do not equal its credits — CRITICAL financial rule */
class JournalImbalanceException extends BusinessRuleException
{
    public function __construct(float $totalDebit, float $totalCredit)
    {
        $variance = abs($totalDebit - $totalCredit);
        parent::__construct(
            "Journal imbalance detected: Debit={$totalDebit}, Credit={$totalCredit}, Variance={$variance}",
            'DOUBLE_ENTRY_VIOLATION'
        );
    }
}
