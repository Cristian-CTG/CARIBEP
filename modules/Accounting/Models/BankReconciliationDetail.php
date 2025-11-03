<?php

namespace Modules\Accounting\Models;

use App\Models\Tenant\ModelTenant;

class BankReconciliationDetail extends ModelTenant
{
    protected $fillable = [
        'bank_reconciliation_id',
        'journal_entry_detail_id',
        'type',
        'date',
        'third_party_name',
        'source',
        'support_number',
        'check',
        'concept',
        'value',
    ];

    public function bankReconciliation()
    {
        return $this->belongsTo(BankReconciliation::class);
    }

    public function journalEntryDetail()
    {
        return $this->belongsTo(JournalEntryDetail::class);
    }
}