<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('sku', 50)->unique(); // e.g., GLD-22K-RING-001
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['SKU_BASED', 'WEIGHT_BASED', 'BATCH'])->default('SKU_BASED');
            $table->enum('metal_type', ['GOLD_22K', 'GOLD_18K', 'GOLD_14K', 'SILVER', 'PLATINUM', 'NONE'])
                  ->default('NONE');
            $table->string('hsn_code', 20); // mandatory for GST
            $table->decimal('gst_rate', 5, 2)->default(0); // e.g., 3.00 for jewellery
            $table->decimal('unit_price', 12, 2)->default(0); // for SKU-based items
            $table->string('unit', 20)->default('PCS'); // PCS, KG, GRAM, etc.

            // Weight-based specific
            $table->decimal('making_charges', 10, 2)->default(0);
            $table->enum('making_charges_type', ['FLAT', 'PERCENTAGE'])->default('FLAT');
            $table->decimal('wastage_percentage', 5, 2)->default(0); // 0-15%
            $table->decimal('hallmark_charge', 8, 2)->default(0);

            // Stock thresholds
            $table->decimal('reorder_point', 12, 3)->default(0);
            $table->decimal('max_stock_level', 12, 3)->default(0);

            // Valuation
            $table->enum('valuation_method', ['FIFO', 'WEIGHTED_AVG'])->default('WEIGHTED_AVG');
            $table->decimal('cost_price', 12, 2)->default(0); // current weighted avg cost

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Per-store stock levels
        Schema::create('stock_levels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('products');
            $table->foreignUuid('store_id')->constrained('stores');
            $table->decimal('quantity', 12, 3)->default(0); // grams for weight-based
            $table->decimal('reserved_quantity', 12, 3)->default(0);
            $table->decimal('available_quantity', 12, 3)->storedAs('quantity - reserved_quantity');
            $table->timestamps();

            $table->unique(['product_id', 'store_id']);
        });

        // Every stock movement logged
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('products');
            $table->foreignUuid('store_id')->constrained('stores');
            $table->enum('movement_type', ['SALE', 'PURCHASE', 'RETURN', 'ADJUSTMENT', 'DAMAGE', 'TRANSFER']);
            $table->decimal('quantity_change', 12, 3); // positive=add, negative=deduct
            $table->decimal('quantity_before', 12, 3);
            $table->decimal('quantity_after', 12, 3);
            $table->decimal('unit_cost', 12, 2)->nullable(); // cost at time of movement
            $table->string('reference_id')->nullable(); // invoice_id, grn_id, etc.
            $table->string('reason')->nullable();
            $table->foreignUuid('moved_by')->constrained('users');
            $table->timestamps();

            $table->index(['product_id', 'store_id', 'created_at']);
        });

        // Live metal rates
        Schema::create('metal_rates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('metal_type', ['GOLD_22K', 'GOLD_18K', 'GOLD_14K', 'SILVER', 'PLATINUM']);
            $table->decimal('rate_per_gram', 10, 2);
            $table->date('rate_date');
            $table->string('source')->default('MANUAL'); // MANUAL, API
            $table->foreignUuid('set_by')->constrained('users');
            $table->timestamps();

            $table->index(['metal_type', 'rate_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metal_rates');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_levels');
        Schema::dropIfExists('products');
    }
};
