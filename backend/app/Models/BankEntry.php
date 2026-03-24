<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankEntry extends Model
{
    use HasUuids;

    protected $fillable = [
        'bank_statement_id', 'entry_date', 'narration',
        'credit_amount', 'debit_amount', 'running_balance',
        'reference_number', 'status',
    ];

    protected $casts = [
        'entry_date'      => 'date',
        'credit_amount'   => 'decimal:2',
        'debit_amount'    => 'decimal:2',
        'running_balance' => 'decimal:2',
    ];

    public function bankStatement(): BelongsTo { return $this->belongsTo(BankStatement::class); }
}
