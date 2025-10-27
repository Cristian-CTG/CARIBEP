<?php

namespace Modules\Accounting\Models;

use App\Models\Tenant\ModelTenant;
use Hyn\Tenancy\Traits\UsesTenantConnection;
use App\Models\Tenant\BankAccount;


class JournalEntryDetail extends ModelTenant
{
    use UsesTenantConnection;

    protected $fillable = ['journal_entry_id', 'chart_of_account_id', 'debit', 'credit', 'third_party_id', 'payment_method_name', 'bank_account_id', 'cash_id'];

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function chartOfAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }
    public function thirdParty()
    {
        return $this->belongsTo(ThirdParty::class, 'third_party_id');
    }
    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }
}