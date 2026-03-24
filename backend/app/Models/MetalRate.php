<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetalRate extends Model
{
    use HasUuids;

    protected $fillable = [
        'metal_type', 'rate_per_gram', 'rate_date', 'source', 'set_by',
    ];

    protected $casts = [
        'rate_per_gram' => 'decimal:2',
        'rate_date'     => 'date',
    ];

    public function setBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'set_by');
    }

    /** Get the latest rate for a metal type */
    public static function latestFor(string $metalType): ?self
    {
        return static::where('metal_type', $metalType)
            ->orderByDesc('rate_date')
            ->orderByDesc('created_at')
            ->first();
    }
}
