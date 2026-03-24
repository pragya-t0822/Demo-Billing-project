<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/** Thrown when a business rule is violated (e.g., insufficient stock, ledger mismatch) */
class BusinessRuleException extends Exception
{
    public function __construct(string $message, private readonly string $rule = '')
    {
        parent::__construct($message);
    }

    public function getRule(): string
    {
        return $this->rule;
    }
}
