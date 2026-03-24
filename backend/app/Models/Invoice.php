<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Invoice extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'invoice_number', 'store_id', 'customer_id', 'fiscal_period_id',
        'invoice_date', 'due_date', 'subtotal', 'total_discount',
        'taxable_amount', 'cgst_total', 'sgst_total', 'igst_total',
        'grand_total', 'amount_paid', 'outstanding_balance',
        'supply_type', 'status', 'payment_mode', 'notes',
        'journal_entry_id', 'created_by',
    ];

    protected $casts = [
        'invoice_date'       => 'date',
        'due_date'           => 'date',
        'subtotal'           => 'decimal:2',
        'total_discount'     => 'decimal:2',
        'taxable_amount'     => 'decimal:2',
        'cgst_total'         => 'decimal:2',
        'sgst_total'         => 'decimal:2',
        'igst_total'         => 'decimal:2',
        'grand_total'        => 'decimal:2',
        'amount_paid'        => 'decimal:2',
        'outstanding_balance'=> 'decimal:2',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function fiscalPeriod(): BelongsTo
    {
        return $this->belongsTo(FiscalPeriod::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(InvoiceLineItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function creditNote(): HasOne
    {
        return $this->hasOne(CreditNote::class);
    }

    public function recoveryRecord(): HasOne
    {
        return $this->hasOne(RecoveryRecord::class);
    }

    public function isDraft(): bool     { return $this->status === 'DRAFT'; }
    public function isConfirmed(): bool { return $this->status === 'CONFIRMED'; }
    public function isPaid(): bool      { return $this->status === 'PAID'; }
    public function isCancelled(): bool { return $this->status === 'CANCELLED'; }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast()
            && in_array($this->status, ['CONFIRMED', 'PARTIAL']);
    }
}
