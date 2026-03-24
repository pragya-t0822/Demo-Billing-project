<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GatewaySettlement extends Model
{
    use HasUuids;

    protected $fillable = [
        'store_id', 'gateway', 'gateway_txn_id', 'system_payment_id',
        'settlement_date', 'settlement_utr', 'gross_amount',
        'fee_rate', 'fee_amount', 'gst_on_fee', 'net_settled',
        'status', 'journal_entry_id',
    ];

    protected $casts = [
        'settlement_date' => 'date',
        'gross_amount'    => 'decimal:2',
        'fee_rate'        => 'decimal:4',
        'fee_amount'      => 'decimal:2',
        'gst_on_fee'      => 'decimal:2',
        'net_settled'     => 'decimal:2',
    ];

    public function store(): BelongsTo { return $this->belongsTo(Store::class); }
}
