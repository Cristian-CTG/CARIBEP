<?php

namespace Modules\Accounting\Models;

use App\Models\Tenant\ModelTenant;
use Hyn\Tenancy\Traits\UsesTenantConnection;

class ChartAccountSaleConfiguration extends ModelTenant
{
    use UsesTenantConnection;
    protected $table = 'chart_account_sale_configurations';

    protected $fillable = [
        'income_account',
        'sales_returns_account',
        'accounting_clasification'
    ];

}
