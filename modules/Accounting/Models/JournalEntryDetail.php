<?php

namespace Modules\Accounting\Models;

use App\Models\Tenant\ModelTenant;
use Hyn\Tenancy\Traits\UsesTenantConnection;


class JournalEntryDetail extends ModelTenant
{
    use UsesTenantConnection;

    protected $fillable = ['journal_entry_id', 'chart_of_account_id', 'debit', 'credit'];

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function chartOfAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }
}