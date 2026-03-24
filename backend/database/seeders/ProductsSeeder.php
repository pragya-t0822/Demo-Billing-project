<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Product;
use App\Models\StockLevel;
use App\Models\Store;
use Illuminate\Database\Seeder;

/**
 * ProductsSeeder — seeds demo jewellery and accessory products with initial stock levels.
 * Weight-based items (gold/silver) are valued at unit_price per gram.
 * SKU-based items are valued as fixed unit price.
 */
class ProductsSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::where('code', 'STR-001')->first();

        $products = [
            // Weight-based gold items
            [
                'sku'                 => 'GLD-RING-22K-001',
                'name'                => 'Gold Ring 22K',
                'type'                => 'WEIGHT_BASED',
                'metal_type'          => 'GOLD_22K',
                'hsn_code'            => '7113',
                'gst_rate'            => 3.00,
                'unit'                => 'gm',
                'unit_price'          => 6850.00,
                'making_charges'      => 12.00,
                'making_charges_type' => 'PERCENTAGE',
                'wastage_percentage'  => 0.00,
                'reorder_point'       => 5.000,
                'cost_price'          => 6500.00,
                'stock_qty'           => 45.750,
            ],
            [
                'sku'                 => 'GLD-CHAIN-22K-001',
                'name'                => 'Gold Chain 22K 20"',
                'type'                => 'WEIGHT_BASED',
                'metal_type'          => 'GOLD_22K',
                'hsn_code'            => '7113',
                'gst_rate'            => 3.00,
                'unit'                => 'gm',
                'unit_price'          => 6850.00,
                'making_charges'      => 10.00,
                'making_charges_type' => 'PERCENTAGE',
                'wastage_percentage'  => 0.00,
                'reorder_point'       => 20.000,
                'cost_price'          => 6500.00,
                'stock_qty'           => 124.250,
            ],
            [
                'sku'                 => 'GLD-EARRING-22K-001',
                'name'                => 'Gold Earrings 22K',
                'type'                => 'WEIGHT_BASED',
                'metal_type'          => 'GOLD_22K',
                'hsn_code'            => '7113',
                'gst_rate'            => 3.00,
                'unit'                => 'gm',
                'unit_price'          => 6850.00,
                'making_charges'      => 15.00,
                'making_charges_type' => 'PERCENTAGE',
                'wastage_percentage'  => 0.00,
                'reorder_point'       => 10.000,
                'cost_price'          => 6500.00,
                'stock_qty'           => 12.100,
            ],
            // Weight-based silver items
            [
                'sku'                 => 'SLV-BANGLE-001',
                'name'                => 'Silver Bangle Set',
                'type'                => 'WEIGHT_BASED',
                'metal_type'          => 'SILVER',
                'hsn_code'            => '7113',
                'gst_rate'            => 3.00,
                'unit'                => 'gm',
                'unit_price'          => 84.50,
                'making_charges'      => 8.00,
                'making_charges_type' => 'PERCENTAGE',
                'wastage_percentage'  => 0.00,
                'reorder_point'       => 100.000,
                'cost_price'          => 80.00,
                'stock_qty'           => 380.000,
            ],
            // SKU-based accessories
            [
                'sku'                 => 'ACC-BOX-VEL-001',
                'name'                => 'Velvet Jewellery Box',
                'type'                => 'SKU_BASED',
                'metal_type'          => 'NONE', // ENUM is NOT NULL; use 'NONE' for non-metal items
                'hsn_code'            => '4202',
                'gst_rate'            => 18.00,
                'unit'                => 'pcs',
                'unit_price'          => 250.00,
                'making_charges'      => 0,
                'making_charges_type' => 'FLAT',
                'wastage_percentage'  => 0,
                'reorder_point'       => 10.000,
                'cost_price'          => 150.00,
                'stock_qty'           => 45.000,
            ],
            [
                'sku'                 => 'ACC-CLEAN-001',
                'name'                => 'Jewellery Cleaning Cloth',
                'type'                => 'SKU_BASED',
                'metal_type'          => 'NONE', // ENUM is NOT NULL; use 'NONE' for non-metal items
                'hsn_code'            => '6307',
                'gst_rate'            => 5.00,
                'unit'                => 'pcs',
                'unit_price'          => 50.00,
                'making_charges'      => 0,
                'making_charges_type' => 'FLAT',
                'wastage_percentage'  => 0,
                'reorder_point'       => 10.000,
                'cost_price'          => 20.00,
                'stock_qty'           => 2.000,
            ],
        ];

        foreach ($products as $p) {
            $stockQty = $p['stock_qty'];
            unset($p['stock_qty']);

            /** @var Product $product */
            $product = Product::firstOrCreate(
                ['sku' => $p['sku']],
                array_merge($p, ['is_active' => true, 'valuation_method' => 'WEIGHTED_AVG'])
            );

            if ($store instanceof Store) {
                StockLevel::firstOrCreate(
                    ['product_id' => $product->id, 'store_id' => $store->id],
                    ['quantity' => $stockQty, 'reserved_quantity' => 0]
                );
            }
        }

        $this->command->info('✅ ' . count($products) . ' demo products seeded with stock levels.');
    }
}
