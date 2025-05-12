<?php

namespace Modules\Accounting\Models;

use App\Models\Tenant\ModelTenant;
use Hyn\Tenancy\Traits\UsesTenantConnection;

class AccountingChartAccountConfiguration extends ModelTenant
{   
    use UsesTenantConnection;
    protected $table = 'accounting_chart_account_configurations';

    protected $fillable = [
        'inventory_account',
        'inventory_adjustment_account',
        'sale_cost_account',
        'customer_receivable_account',
        'customer_returns_account',
        'supplier_payable_account',
        'supplier_returns_account',
        'retained_earning_account',
        'profit_period_account',
        'lost_period_account',
        'adjustment_opening_balance_banks_account',
        'adjustment_opening_balance_banks_inventory',
    ];

    
}
