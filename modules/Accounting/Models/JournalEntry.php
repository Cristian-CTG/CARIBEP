<?php

namespace Modules\Accounting\Models;

use Illuminate\Support\Facades\DB;
use App\Models\Tenant\ModelTenant;
use Hyn\Tenancy\Traits\UsesTenantConnection;
use App\Models\Tenant\Document;
use App\Models\Tenant\Purchase;
use App\Models\Tenant\DocumentPos;
use Modules\Payroll\Models\DocumentPayroll;
use Modules\Purchase\Models\SupportDocument;
use Modules\Expense\Models\Expense;


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
        'support_document_id',
        'expense_id',
        'document_pos_id',
        'document_payroll_id',
        'status',
        'number',
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

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    public function support_document()
    {
        return $this->belongsTo(SupportDocument::class, 'support_document_id');
    }

    public function expense()
    {
        return $this->belongsTo(Expense::class, 'expense_id');
    }

    public function document_pos()
    {
        return $this->belongsTo(DocumentPos::class, 'document_pos_id');
    }

    public function document_payroll()
    {
        return $this->belongsTo(DocumentPayroll::class, 'document_payroll_id');
    }

    /**
     * Obtiene el siguiente número de journal para el prefijo de journal especificado.
     *
     * @param int $journalPrefixId Identificador del prefijo de journal.
     * @return int El siguiente número de journal.
     */
    public static function getNextNumber($journalPrefixId)
    {
        // Bloquea la tabla para evitar duplicados en concurrencia
        $last = self::where('journal_prefix_id', $journalPrefixId)
            ->orderByDesc('number')
            ->lockForUpdate()
            ->first();

        return $last ? $last->number + 1 : 1;
    }

    /**
     * Crea un nuevo asiento contable con un número único,
     * bloqueando la tabla para evitar duplicados en concurrencia.
     *
     * @param array $data Los datos del asiento contable.
     * @return \Modules\Accounting\Models\JournalEntry El asiento contable creado.
     */
    public static function createWithNumber(array $data)
    {
        return DB::transaction(function () use ($data) {
            $number = self::getNextNumber($data['journal_prefix_id']);
            $data['number'] = $number;
            return self::create($data);
        });
    }

    /**
     * Retorna el nombre y número/prefijo del documento relacionado al asiento.
     * @return string
     */
    public function getRelatedComprobanteNumber()
    {
        // Documento de venta
        if ($this->document) {
            return $this->document->prefix . '-' . $this->document->number;
        }
        // Compra
        if ($this->purchase) {
            return ($this->purchase->series ?? $this->purchase->prefix ?? '') . '-' . $this->purchase->number;
        }
        // Documento soporte
        if ($this->support_document) {
            return $this->support_document->prefix . '-' . $this->support_document->number;
        }
        // Gasto
        if ($this->expense) {
            return 'Gasto #' . $this->expense->number;
        }
        // POS
        if ($this->document_pos) {
            return ($this->document_pos->series ?? $this->document_pos->prefix ?? '') . '-' . $this->document_pos->number;
        }
        // Nómina
        if ($this->document_payroll) {
            return $this->document_payroll->prefix . '-' . $this->document_payroll->consecutive;
        }
        return '-';
    }
}
