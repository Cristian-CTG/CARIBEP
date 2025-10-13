<?php

namespace App\Models\Tenant;

use Modules\Finance\Models\GlobalPayment;
use Illuminate\Database\Query\Builder;


class Cash extends ModelTenant
{
    // protected $with = ['cash_documents'];

    protected $table = 'cash';

    protected $fillable = [
        'user_id',
        'date_opening',
        'time_opening',
        'date_closed',
        'time_closed',
        'beginning_balance',
        'final_balance',
        'income',
        'state',
        'reference_number',
        'resolution_id'

    ];



    public function user()
    {
        return $this->belongsTo(User::class);
    }

    //obtiene documentos y notas venta
    public function cash_documents()
    {
        return $this->hasMany(CashDocument::class);
    }

    public function scopeWhereTypeUser($query)
    {
        $user = auth()->user();
        return ($user->type == 'seller') ? $query->where('user_id', $user->id) : null;
    }

    public function global_destination()
    {
        return $this->morphMany(GlobalPayment::class, 'destination');
    }

    public function resolution()
    {
        return $this->belongsTo(ConfigurationPos::class, 'resolution_id');
    }
    
    /**
     * 
     * Retornar el balance final (total de ingresos - gastos)
     *
     * Usado en:
     * CashController - Cierre de caja chica
     * 
     * @return double
     */
    public function getSumCashFinalBalance()
    {
        \Log::info('Iniciando cálculo de balance final para caja', ['cash_id' => $this->id]);
        
        $totalDocuments = $this->cash_documents->count();
        \Log::info('Total de documentos encontrados en caja', ['documents_count' => $totalDocuments]);
        
        $totalBalance = 0;
        $processedDocuments = 0;
        $documentsWithPos = 0;
        
        $result = $this->cash_documents->sum(function($row) use (&$totalBalance, &$processedDocuments, &$documentsWithPos){
            $processedDocuments++;
            
            if ($row->document_pos) {
                $documentsWithPos++;
                $documentTotal = $row->document_pos->getTotalCash();
                $totalBalance += $documentTotal;

                \Log::debug('Documento procesado', [
                    'document_id' => $row->id,
                    'document_pos_id' => $row->document_pos->id ?? null,
                    'document_total' => $documentTotal
                ]);
            } else {
                \Log::warning('Documento sin document_pos encontrado', [
                    'document_id' => $row->id,
                    'cash_id' => $this->id
                ]);
            }
            
            return $row->document_pos ? $row->document_pos->getTotalCash() : 0;
        });

        \Log::info('Balance final calculado', [
            'cash_id' => $this->id,
            'total_balance' => $result,
            'total_documents' => $totalDocuments,
            'processed_documents' => $processedDocuments,
            'documents_with_pos' => $documentsWithPos,
            'documents_without_pos' => $processedDocuments - $documentsWithPos
        ]);
        
        return $result;
    }

    
    /**
     * 
     * Filtro para obtener caja abierta del usuario en sesion
     *
     * @param  Builder $query
     * @return Builder
     */
    public function scopeGetOpenCurrentCash($query)
    {
        return $query->where('state', 1)->where('user_id', auth()->id());
    }

}
