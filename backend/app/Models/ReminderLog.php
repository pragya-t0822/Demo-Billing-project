<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReminderLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'recovery_record_id', 'invoice_id', 'reminder_stage',
        'channel', 'status', 'message_content', 'gateway_message_id',
        'sent_by', 'sent_at', 'error_message',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function recoveryRecord(): BelongsTo
    {
        return $this->belongsTo(RecoveryRecord::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
