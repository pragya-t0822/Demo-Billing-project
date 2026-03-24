<?php

declare(strict_types=1);

namespace App\Exceptions;

class InsufficientStockException extends BusinessRuleException
{
    public function __construct(string $sku, float $required, float $available)
    {
        parent::__construct(
            "Insufficient stock for {$sku}: required={$required}, available={$available}",
            'NEGATIVE_STOCK_VIOLATION'
        );
    }
}
