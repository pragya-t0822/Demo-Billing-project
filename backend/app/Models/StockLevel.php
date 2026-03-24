<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLevel extends Model
{
    use HasUuids;

    protected $fillable = [
        'product_id', 'store_id', 'quantity', 'reserved_quantity',
    ];

    protected $casts = [
        'quantity'          => 'decimal:3',
        'reserved_quantity' => 'decimal:3',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function getAvailableQuantityAttribute(): float
    {
        return (float) $this->quantity - (float) $this->reserved_quantity;
    }

    public function hasStock(float $required): bool
    {
        return $this->getAvailableQuantityAttribute() >= $required;
    }
}
