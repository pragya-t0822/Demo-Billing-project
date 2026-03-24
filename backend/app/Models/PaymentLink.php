<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentLink extends Model
{
    use HasUuids;

    protected $fillable = [
        'link_number', 'invoice_id', 'customer_id',
        'short_url', 'amount', 'payment_methods',
        'expires_at', 'status', 'payment_id', 'created_by',
    ];

    protected $casts = [
        'amount'          => 'decimal:2',
        'payment_methods' => 'array',
        'expires_at'      => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at?->isPast() ?? false;
    }

    public function isActive(): bool
    {
        return $this->status === 'ACTIVE' && ! $this->isExpired();
    }
}
