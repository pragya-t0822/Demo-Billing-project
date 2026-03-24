<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'code', 'name', 'phone', 'email', 'address', 'city', 'state',
        'state_code', 'pincode', 'gstin', 'tier', 'credit_limit',
        'outstanding_balance', 'is_active',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function recoveryRecords(): HasMany
    {
        return $this->hasMany(RecoveryRecord::class);
    }

    /** Determine supply type based on customer state vs store state */
    public function getSupplyType(Store $store): string
    {
        if (! $this->state_code || ! $store->state_code) {
            return 'INTRA';
        }

        return $this->state_code === $store->state_code ? 'INTRA' : 'INTER';
    }
}
