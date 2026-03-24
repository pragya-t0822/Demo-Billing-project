<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasUuids;

    protected $fillable = [
        'payment_number', 'invoice_id', 'store_id', 'customer_id',
        'payment_mode', 'amount_paid', 'gateway_transaction_id',
        'cheque_number', 'bank_reference', 'status',
        'journal_entry_id', 'recorded_by', 'payment_date',
    ];

    protected $casts = [
        'amount_paid'  => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function isDigital(): bool
    {
        return in_array($this->payment_mode, ['CARD', 'UPI', 'NETBANKING']);
    }
}
