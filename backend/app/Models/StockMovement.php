<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasUuids;

    protected $fillable = [
        'product_id', 'store_id', 'movement_type',
        'quantity_change', 'quantity_before', 'quantity_after',
        'unit_cost', 'reference_id', 'reason', 'moved_by',
    ];

    protected $casts = [
        'quantity_change' => 'decimal:3',
        'quantity_before' => 'decimal:3',
        'quantity_after'  => 'decimal:3',
        'unit_cost'       => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function movedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moved_by');
    }
}
