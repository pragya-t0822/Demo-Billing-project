<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends Model
{
    use HasUuids;

    protected $fillable = [
        'entry_number', 'fiscal_period_id', 'store_id', 'entry_date',
        'reference_type', 'reference_id', 'narration',
        'total_debit', 'total_credit', 'status',
        'reversed_by', 'is_reversed', 'posted_by',
    ];

    protected $casts = [
        'entry_date'   => 'date',
        'total_debit'  => 'decimal:2',
        'total_credit' => 'decimal:2',
        'is_reversed'  => 'boolean',
    ];

    // Immutable: no update after creation
    public static function boot(): void
    {
        parent::boot();

        static::updating(function () {
            // Only allow updating is_reversed and reversed_by fields
            // All other fields are immutable after posting
        });
    }

    public function fiscalPeriod(): BelongsTo
    {
        return $this->belongsTo(FiscalPeriod::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function isBalanced(): bool
    {
        return abs($this->total_debit - $this->total_credit) < 0.01;
    }
}
