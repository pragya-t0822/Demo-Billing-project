<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankStatement extends Model
{
    use HasUuids;

    protected $fillable = [
        'store_id', 'bank_name', 'account_number_masked',
        'statement_date', 'opening_balance', 'closing_balance',
        'status', 'imported_by',
    ];

    protected $casts = [
        'statement_date'  => 'date',
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
    ];

    public function store(): BelongsTo    { return $this->belongsTo(Store::class); }
    public function importedBy(): BelongsTo { return $this->belongsTo(User::class, 'imported_by'); }
    public function entries(): HasMany    { return $this->hasMany(BankEntry::class); }
}
