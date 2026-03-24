<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'sku', 'name', 'description', 'type', 'metal_type',
        'hsn_code', 'gst_rate', 'unit_price', 'unit',
        'making_charges', 'making_charges_type', 'wastage_percentage', 'hallmark_charge',
        'reorder_point', 'max_stock_level', 'valuation_method', 'cost_price',
        'is_active',
    ];

    protected $casts = [
        'gst_rate'            => 'decimal:2',
        'unit_price'          => 'decimal:2',
        'making_charges'      => 'decimal:2',
        'wastage_percentage'  => 'decimal:2',
        'hallmark_charge'     => 'decimal:2',
        'reorder_point'       => 'decimal:3',
        'max_stock_level'     => 'decimal:3',
        'cost_price'          => 'decimal:2',
        'is_active'           => 'boolean',
    ];

    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function isWeightBased(): bool
    {
        return $this->type === 'WEIGHT_BASED';
    }

    public function isSkuBased(): bool
    {
        return $this->type === 'SKU_BASED';
    }

    /** Get stock level for a specific store */
    public function stockForStore(string $storeId): ?StockLevel
    {
        return $this->stockLevels()->where('store_id', $storeId)->first();
    }
}
