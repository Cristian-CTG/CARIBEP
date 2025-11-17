<?php

namespace Modules\Expense\Models;

use App\Models\Tenant\Item;
use App\Models\Tenant\ModelTenant;
use Modules\Accounting\Models\ChartOfAccount;

class ExpenseItem extends ModelTenant
{

    public $timestamps = false;
    
    protected $fillable = [
        'expense_id',
        'description',
        'total', 
        'chart_of_account_id',
    ];

    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }

    public function chart_of_account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }
 
}