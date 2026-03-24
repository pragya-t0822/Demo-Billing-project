<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLineItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'invoice_id', 'product_id', 'product_name', 'hsn_code',
        'quantity', 'gross_weight_grams', 'net_weight_grams',
        'unit_price', 'making_charges', 'wastage_amount', 'hallmark_charge',
        'base_price', 'discount_amount', 'taxable_amount',
        'gst_rate', 'cgst_rate', 'sgst_rate', 'igst_rate',
        'cgst_amount', 'sgst_amount', 'igst_amount', 'total_tax', 'line_total',
        'metal_rate_per_gram', 'metal_rate_timestamp',
    ];

    protected $casts = [
        'quantity'              => 'decimal:3',
        'gross_weight_grams'    => 'decimal:3',
        'net_weight_grams'      => 'decimal:3',
        'unit_price'            => 'decimal:2',
        'making_charges'        => 'decimal:2',
        'wastage_amount'        => 'decimal:2',
        'hallmark_charge'       => 'decimal:2',
        'base_price'            => 'decimal:2',
        'discount_amount'       => 'decimal:2',
        'taxable_amount'        => 'decimal:2',
        'gst_rate'              => 'decimal:2',
        'cgst_rate'             => 'decimal:2',
        'sgst_rate'             => 'decimal:2',
        'igst_rate'             => 'decimal:2',
        'cgst_amount'           => 'decimal:2',
        'sgst_amount'           => 'decimal:2',
        'igst_amount'           => 'decimal:2',
        'total_tax'             => 'decimal:2',
        'line_total'            => 'decimal:2',
        'metal_rate_per_gram'   => 'decimal:2',
        'metal_rate_timestamp'  => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
