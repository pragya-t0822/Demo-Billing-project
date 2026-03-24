<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartOfAccount extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'chart_of_accounts';

    protected $fillable = [
        'code', 'name', 'type', 'normal_balance', 'parent_code',
        'is_system_account', 'is_active', 'description',
    ];

    protected $casts = [
        'is_system_account' => 'boolean',
        'is_active' => 'boolean',
    ];

    // CoA code ranges
    const ASSET_RANGE       = [1000, 1999];
    const LIABILITY_RANGE   = [2000, 2999];
    const EQUITY_RANGE      = [3000, 3999];
    const REVENUE_RANGE     = [4000, 4999];
    const COGS_RANGE        = [5000, 5999];
    const EXPENSE_RANGE     = [6000, 6999];
    const TAX_RANGE         = [7000, 7999];

    // System account codes
    const ACCOUNTS_RECEIVABLE     = '1200';
    const CASH_IN_HAND             = '1100';
    const BANK_ACCOUNT             = '1110';
    const GATEWAY_CLEARING         = '1120';
    const INVENTORY_ASSET          = '1300';
    const ACCOUNTS_PAYABLE         = '2100';
    const CGST_PAYABLE             = '2200';
    const SGST_PAYABLE             = '2210';
    const IGST_PAYABLE             = '2220';
    const GST_INPUT_CREDIT         = '1400';
    const SALES_REVENUE            = '4000';
    const GATEWAY_FEE_EXPENSE      = '6100';
    const CUSTOMER_ADVANCE         = '2300';

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class, 'account_code', 'code');
    }
}
