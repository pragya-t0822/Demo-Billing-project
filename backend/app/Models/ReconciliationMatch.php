<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReconciliationMatch extends Model
{
    use HasUuids;

    protected $fillable = [
        'bank_entry_id', 'system_payment_id',
        'match_confidence', 'match_criteria', 'status', 'confirmed_by',
    ];

    protected $casts = [
        'match_criteria' => 'array',
    ];

    public function bankEntry(): BelongsTo  { return $this->belongsTo(BankEntry::class); }
    public function confirmedBy(): BelongsTo { return $this->belongsTo(User::class, 'confirmed_by'); }
}
