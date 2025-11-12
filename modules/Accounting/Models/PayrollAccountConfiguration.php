<?php

namespace Modules\Accounting\Models;

use App\Models\Tenant\ModelTenant;
use Hyn\Tenancy\Traits\UsesTenantConnection;

class PayrollAccountConfiguration extends ModelTenant
{
    use UsesTenantConnection;

    protected $table = 'payroll_account_configurations';

    protected $fillable = [
        'salary_account',
        'transportation_allowance_account',
        'health_account',
        'pension_account',
        'vacation_account',
        'service_bonus_account',
        'extra_service_bonus_account',
        'severance_account',
        'severance_interest_account',
        'other_bonuses_account',
        'net_payable_account',
    ];
}