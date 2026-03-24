<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\Store;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

/**
 * StockService — manages real-time stock levels atomically.
 *
 * Rules:
 *  - All operations run in DB transactions
 *  - Never allow negative stock unless store.allow_negative_stock = true
 *  - Every movement logged in stock_movements
 *  - low_stock_alert checked after every deduction
 */
class StockService
{
    public function __construct(
        private readonly MetalRateService $metalRateService,
        private readonly AuditService $audit,
    ) {}

    /**
     * Deduct stock — called by billing agent on invoice confirmation.
     */
    public function deductStock(
        string $productId,
        string $storeId,
        float $quantity,
        string $referenceId,
        string $movedBy,
        string $reason = 'SALE'
    ): StockMovement {
        return DB::transaction(function () use ($productId, $storeId, $quantity, $referenceId, $movedBy, $reason) {
            // Lock the row for update to prevent race conditions
            /** @var StockLevel|null $stockLevel */
            $stockLevel = StockLevel::where('product_id', $productId)
                ->where('store_id', $storeId)
                ->lockForUpdate()
                ->first();

            /** @var Store $store */
            $store = Store::findOrFail($storeId);
            /** @var Product $product */
            $product = Product::findOrFail($productId);

            $currentQty = (float) ($stockLevel?->quantity ?? 0);
            $newQty     = $currentQty - $quantity;

            // Enforce no-negative-stock rule
            if ($newQty < 0 && ! $store->allow_negative_stock) {
                throw new InsufficientStockException($product->sku, $quantity, $currentQty);
            }

            // Update or create stock level
            if ($stockLevel) {
                $stockLevel->update(['quantity' => $newQty]);
            } else {
                StockLevel::create([
                    'product_id' => $productId,
                    'store_id'   => $storeId,
                    'quantity'   => $newQty,
                ]);
            }

            // Log movement
            /** @var \App\Models\StockMovement $movement */
            $movement = StockMovement::create([
                'product_id'      => $productId,
                'store_id'        => $storeId,
                'movement_type'   => 'SALE',
                'quantity_change' => -$quantity,
                'quantity_before' => $currentQty,
                'quantity_after'  => $newQty,
                'reference_id'    => $referenceId,
                'reason'          => $reason,
                'moved_by'        => $movedBy,
            ]);

            // Check low stock threshold
            if ($newQty <= (float) $product->reorder_point) {
                $this->triggerLowStockAlert($product, $storeId, $newQty);
            }

            return $movement;
        });
    }

    /**
     * Restore stock — called on invoice cancellation/return.
     */
    public function restoreStock(
        string $productId,
        string $storeId,
        float $quantity,
        string $referenceId,
        string $movedBy,
        string $movementType = 'RETURN',
        string $reason = 'RETURN'
    ): StockMovement {
        return DB::transaction(function () use ($productId, $storeId, $quantity, $referenceId, $movedBy, $movementType, $reason) {
            /** @var StockLevel|null $stockLevel */
            $stockLevel = StockLevel::where('product_id', $productId)
                ->where('store_id', $storeId)
                ->lockForUpdate()
                ->first();

            $currentQty = (float) ($stockLevel?->quantity ?? 0);
            $newQty     = $currentQty + $quantity;

            if ($stockLevel) {
                $stockLevel->update(['quantity' => $newQty]);
            } else {
                StockLevel::create(['product_id' => $productId, 'store_id' => $storeId, 'quantity' => $newQty]);
            }

            /** @var StockMovement $movement */
            $movement = StockMovement::create([
                'product_id'      => $productId,
                'store_id'        => $storeId,
                'movement_type'   => $movementType,
                'quantity_change' => $quantity,
                'quantity_before' => $currentQty,
                'quantity_after'  => $newQty,
                'reference_id'    => $referenceId,
                'reason'          => $reason,
                'moved_by'        => $movedBy,
            ]);

            return $movement;
        });
    }

    /**
     * Manual stock adjustment (admin function).
     */
    public function adjustStock(
        string $productId,
        string $storeId,
        float $adjustment,
        string $reason,
        string $movedBy
    ): StockMovement {
        if (empty($reason)) {
            throw new BusinessRuleException('Reason is mandatory for manual stock adjustments.', 'MISSING_ADJUSTMENT_REASON');
        }

        return DB::transaction(function () use ($productId, $storeId, $adjustment, $reason, $movedBy) {
            /** @var StockLevel|null $stockLevel */
            $stockLevel = StockLevel::where('product_id', $productId)
                ->where('store_id', $storeId)
                ->lockForUpdate()
                ->first();

            $currentQty = (float) ($stockLevel?->quantity ?? 0);
            $newQty     = $currentQty + $adjustment;

            /** @var Store $store */
            $store = Store::findOrFail($storeId);
            if ($newQty < 0 && ! $store->allow_negative_stock) {
                throw new BusinessRuleException("Adjustment would result in negative stock ({$newQty}).", 'NEGATIVE_STOCK_VIOLATION');
            }

            if ($stockLevel) {
                $stockLevel->update(['quantity' => $newQty]);
            } else {
                StockLevel::create(['product_id' => $productId, 'store_id' => $storeId, 'quantity' => $newQty]);
            }

            /** @var \App\Models\StockMovement $movement */
            $movement = StockMovement::create([
                'product_id'      => $productId,
                'store_id'        => $storeId,
                'movement_type'   => 'ADJUSTMENT',
                'quantity_change' => $adjustment,
                'quantity_before' => $currentQty,
                'quantity_after'  => $newQty,
                'reason'          => $reason,
                'moved_by'        => $movedBy,
            ]);

            $this->audit->log('STOCK_ADJUSTMENT', 'StockMovement', $movement->id,
                ['quantity' => $currentQty],
                ['quantity' => $newQty, 'reason' => $reason],
                storeId: $storeId
            );

            return $movement;
        });
    }

    private function triggerLowStockAlert(Product $product, string $storeId, float $currentQty): void
    {
        // Log a warning audit entry for low stock
        $this->audit->log(
            action: 'LOW_STOCK_ALERT',
            entityType: 'Product',
            entityId: $product->id,
            afterState: [
                'sku'           => $product->sku,
                'current_qty'   => $currentQty,
                'reorder_point' => $product->reorder_point,
            ],
            severity: 'WARNING',
            storeId: $storeId
        );
    }
}
