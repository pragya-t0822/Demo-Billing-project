<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecoveryRecord extends Model
{
    use HasUuids;

    protected $fillable = [
        'invoice_id', 'customer_id', 'store_id',
        'outstanding_balance', 'due_date', 'days_overdue',
        'recovery_stage', 'status', 'last_reminder_sent_at',
        'promise_to_pay_date', 'promise_to_pay_amount',
    ];

    protected $casts = [
        'outstanding_balance'   => 'decimal:2',
        'promise_to_pay_amount' => 'decimal:2',
        'due_date'              => 'date',
        'promise_to_pay_date'   => 'date',
        'last_reminder_sent_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function reminderLogs(): HasMany
    {
        return $this->hasMany(ReminderLog::class);
    }

    public function paymentLinks(): HasMany
    {
        return $this->hasMany(PaymentLink::class, 'invoice_id', 'invoice_id');
    }
}
