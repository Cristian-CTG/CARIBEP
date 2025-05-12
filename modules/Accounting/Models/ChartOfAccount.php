<?php

namespace Modules\Accounting\Models;

use App\Models\Tenant\ModelTenant;
use Hyn\Tenancy\Traits\UsesTenantConnection;

class ChartOfAccount extends ModelTenant
{
    use UsesTenantConnection;

    protected $table = 'chart_of_accounts';
    protected $fillable = ['code', 'name', 'type', 'parent_id', 'level', 'status'];

    // Relación padre-hijo (Self-referencing relationship)
    public function parent()
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_id');
    }

    public function journalEntryDetails()
    {
        return $this->hasMany(JournalEntryDetail::class, 'chart_of_account_id');
    }
}
