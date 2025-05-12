<?php

namespace Modules\Accounting\Models;

use App\Models\Tenant\ModelTenant;
use Hyn\Tenancy\Traits\UsesTenantConnection;


class JournalEntry extends ModelTenant
{
    // use UsesTenantConnection;
    protected $table = 'journal_entries';

    protected $fillable = [
        'journal_prefix_id',
        'date',
        'description',
        'document_id',
        'purchase_id',
        'status'
    ];

    public function details()
    {
        return $this->hasMany(JournalEntryDetail::class);
    }

    /**
     * Relación con el modelo JournalPrefix.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function journal_prefix()
    {
        return $this->belongsTo(JournalPrefix::class, 'journal_prefix_id');
    }

    /**
     * Verifica si el asiento está balanceado antes de aprobar.
     *
     * @return bool
     */
    public function canBeApproved()
    {
        // Verifica si el asiento está balanceado antes de aprobar
        $totalDebit = $this->details()->sum('debit');
        $totalCredit = $this->details()->sum('credit');

        return $totalDebit === $totalCredit;
    }

    /**
     * Alcance para obtener solo las entradas de diario publicadas
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    /**
     * Alcance para obtener solo las entradas de diario en borrador
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Determina si el asiento contable puede ser editado.
     *
     * Un asiento contable solo puede ser editado si su estado es "draft" o "rejected".
     *
     * @return bool
     */
    public function isEditable()
    {
        return in_array($this->status, ['draft', 'rejected']);
    }
}
