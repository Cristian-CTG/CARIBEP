<?php

namespace Modules\Accounting\Models;

use App\Models\Tenant\ModelTenant;
use Modules\Accounting\Models\JournalEntry;
use Hyn\Tenancy\Traits\UsesTenantConnection;


class JournalPrefix extends ModelTenant
{
    // use UsesTenantConnection;
    protected $table = 'journal_prefixes';

    protected $fillable = ['prefix', 'description', 'modifiable'];

    /**
     * Get the journal entries associated with the journal prefix.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function journalEntries()
    {
        return $this->hasMany(JournalEntry::class, 'journal_prefix_id');
    }
}
