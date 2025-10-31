<?php
namespace Modules\Accounting\Models;

use App\Models\Tenant\ModelTenant;
use App\Models\Tenant\BankAccount;

class BankReconciliation extends ModelTenant
{
    protected $fillable = [
        'bank_account_id',
        'month',
        'date',
        'saldo_extracto',
        'saldo_libro',
        'diferencia',
        'status',
    ];

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }
    
    public function bankAccounts()
    {
        return BankAccount::where('status', 1)->get();
    }

    public function details()
    {
        return $this->hasMany(BankReconciliationDetail::class);
    }
    
}