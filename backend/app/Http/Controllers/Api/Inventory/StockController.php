<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\SetMetalRateRequest;
use App\Http\Requests\Inventory\StockAdjustmentRequest;
use App\Http\Requests\Inventory\WeightPriceRequest;
use App\Models\Product;
use App\Models\StockLevel;
use App\Services\Inventory\MetalRateService;
use App\Services\Inventory\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockController extends Controller
{
    public function __construct(
        private readonly StockService $stockService,
        private readonly MetalRateService $metalRateService,
    ) {}

    /** GET /api/inventory/:sku */
    public function show(string $sku): JsonResponse
    {
        $product = Product::where('sku', $sku)->with('stockLevels.store')->firstOrFail();
        return $this->success($product);
    }

    /** GET /api/inventory/low-stock */
    public function lowStock(Request $request): JsonResponse
    {
        $storeId = $request->store_id;

        $items = StockLevel::with('product')
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->whereRaw('quantity <= (SELECT reorder_point FROM products WHERE products.id = stock_levels.product_id)')
            ->get()
            ->map(fn($sl) => [
                'sku'             => $sl->product->sku,
                'name'            => $sl->product->name,
                'current_qty'     => $sl->quantity,
                'reorder_point'   => $sl->product->reorder_point,
                'max_stock_level' => $sl->product->max_stock_level,
                'store_id'        => $sl->store_id,
            ]);

        return $this->success($items);
    }

    /** POST /api/inventory/adjustment */
    public function adjustment(StockAdjustmentRequest $request): JsonResponse
    {
        $data = $request->validated();

        $movement = $this->stockService->adjustStock(
            $data['product_id'],
            $data['store_id'],
            (float) $data['adjustment'],
            $data['reason'],
            Auth::id()
        );

        return $this->success($movement, 'Stock adjusted successfully.', 201);
    }

    /** POST /api/inventory/metal-rate */
    public function setMetalRate(SetMetalRateRequest $request): JsonResponse
    {
        $data = $request->validated();

        $rate = $this->metalRateService->setRate(
            $data['metal_type'],
            (float) $data['rate_per_gram'],
            $data['rate_date']
        );

        return $this->success($rate, 'Metal rate updated.', 201);
    }

    /** GET /api/inventory/metal-rate/:type */
    public function getMetalRate(string $metalType): JsonResponse
    {
        $rate = $this->metalRateService->getLiveRate($metalType);
        return $this->success($rate);
    }

    /** POST /api/inventory/weight-price */
    public function calculateWeightPrice(WeightPriceRequest $request): JsonResponse
    {
        $data = $request->validated();

        $result = $this->metalRateService->calculateWeightBasedPrice(
            metalType: $data['metal_type'],
            grossWeightGrams: $data['gross_weight_grams'],
            netWeightGrams: $data['net_weight_grams'],
            makingCharges: $data['making_charges'],
            makingChargesType: $data['making_charges_type'],
            wastagePercentage: $data['wastage_percentage'] ?? 0,
            hallmarkCharge: $data['hallmark_charge'] ?? 0,
            gstRate: $data['gst_rate'] ?? 3,
        );

        return $this->success($result);
    }
}
