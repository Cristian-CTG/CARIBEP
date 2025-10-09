<?php

namespace Modules\Finance\Models;

use App\Models\Tenant\ModelTenant;
use App\Models\Tenant\Cash;
use App\Models\Tenant\BankAccount;
use App\Models\Tenant\SoapType;
use Modules\Sale\Models\QuotationPayment;
use Modules\Expense\Models\ExpensePayment;
use App\Models\Tenant\{
    DocumentPayment,
    SaleNotePayment,
    PurchasePayment,
    DocumentPosPayment
};
use Modules\Purchase\Models\SupportDocumentPayment;
use Modules\Sale\Models\ContractPayment;
use Modules\Sale\Models\RemissionPayment;

class GlobalPayment extends ModelTenant
{

    protected $fillable = [
        'soap_type_id',
        'destination_id',
        'destination_type',
        'payment_id',
        'payment_type', 
    ];
 

    public function soap_type()
    {
        return $this->belongsTo(SoapType::class);
    }

    public function destination()
    {
        return $this->morphTo();
    }
     

    public function payment()
    {
        return $this->morphTo();
    }

    public function doc_payments()
    {
        return $this->belongsTo(DocumentPayment::class, 'payment_id')
                    ->wherePaymentType(DocumentPayment::class);
    }
    public function exp_payment()
    {
        return $this->belongsTo(ExpensePayment::class, 'payment_id')
                    ->wherePaymentType(ExpensePayment::class);
    }
    
    public function sln_payments()
    {
        return $this->belongsTo(SaleNotePayment::class, 'payment_id')
                    ->wherePaymentType(SaleNotePayment::class);
    }

    public function pur_payment()
    {
        return $this->belongsTo(PurchasePayment::class, 'payment_id')
                    ->wherePaymentType(PurchasePayment::class);
    } 

    public function sup_payment()
    {
        return $this->belongsTo(SupportDocumentPayment::class, 'payment_id')
                    ->wherePaymentType(SupportDocumentPayment::class);
    }

    public function quo_payment()
    {
        return $this->belongsTo(QuotationPayment::class, 'payment_id')
                    ->wherePaymentType(QuotationPayment::class);
    } 

    public function con_payment()
    {
        return $this->belongsTo(ContractPayment::class, 'payment_id')
                    ->wherePaymentType(ContractPayment::class);
    }

    public function inc_payment()
    {
        return $this->belongsTo(IncomePayment::class, 'payment_id')
                    ->wherePaymentType(IncomePayment::class);
    }  

    public function rem_payment()
    {
        return $this->belongsTo(RemissionPayment::class, 'payment_id')
                    ->wherePaymentType(RemissionPayment::class);
    } 

    public function doc_pos_payment()
    {
        return $this->belongsTo(DocumentPosPayment::class, 'payment_id')
                    ->wherePaymentType(DocumentPosPayment::class);
    }

    public function getDestinationDescriptionAttribute()
    {
        if ($this->destination_type === Cash::class) {
            return 'Caja';
        }

        $bank = $this->destination && $this->destination->bank ? $this->destination->bank->description : '';
        $currency = $this->destination && $this->destination->currency_type_id ? $this->destination->currency_type_id : '';
        $description = $this->destination && $this->destination->description ? $this->destination->description : '';

        return trim("{$bank} - {$currency} - {$description}", ' -');
    }
     
    public function getTypeRecordAttribute()
    {
        return $this->destination_type === Cash::class ? 'cash':'bank_account';
    }

    public function getInstanceTypeAttribute()
    {
        $instance_type = [
            DocumentPayment::class => 'document',
            SaleNotePayment::class => 'sale_note',
            PurchasePayment::class => 'purchase',
            ExpensePayment::class => 'expense',
            QuotationPayment::class => 'quotation',
            ContractPayment::class => 'contract',
            IncomePayment::class => 'income',
            RemissionPayment::class => 'remission',
            DocumentPosPayment::class => 'document_pos',
            SupportDocumentPayment::class => 'support_document',
        ];

        return $instance_type[$this->payment_type];
    }

    public function getInstanceTypeDescriptionAttribute()
    {

        $description = null;
        
        switch ($this->instance_type) {
            case 'document':
                $description = 'FACTURA ELECTRÓNICA';
                // $description = 'CPE';
                break;
            case 'sale_note':
                $description = 'NOTA DE VENTA';
                break;
            case 'purchase':
                $description = 'COMPRA';
                break;
            case 'expense':
                $description = 'GASTO';
                break;
            case 'quotation':
                $description = 'COTIZACIÓN';
                break;
            case 'contract':
                $description = 'CONTRATO';
                break;
            case 'income':
                $description = 'INGRESO';
                break;
            case 'remission':
                $description = 'REMISIÓN';
            case 'document_pos':
                $description = 'DOCUMENTO POS';
                break;
            case 'support_document':
                $description = 'DOCUMENTO DE SOPORTE';
                break;
             
        } 

        return $description;
    }

    public function getDataPersonAttribute(){

        $record = $this->payment->associated_record_payment;

        switch ($this->instance_type) {

            case 'document':
            case 'sale_note':
            case 'quotation':
            case 'remission':
            case 'document_pos':
            case 'contract':
                $person['name'] = $record->customer->name;
                $person['number'] = $record->customer->number;
                break;
            case 'purchase':
            case 'expense':
                $person['name'] = $record->supplier->name;
                $person['number'] = $record->supplier->number;
                break;
            case 'income':
                $person['name'] = $record->customer;
                $person['number'] = '';
            case 'support_document':
                if ($record && isset($record->supplier)) {
                    $person['name'] = $record->supplier->name ?? '';
                    $person['number'] = $record->supplier->number ?? '';
                }
                break;
        }

        return (object) $person;
    }
    

    public function scopeWhereFilterPaymentType($query, $params)
    {

        return $query->whereHas('doc_payments', function($q) use($params){
                    $q->whereBetween('date_of_payment', [$params->date_start, $params->date_end])
                        ->whereHas('associated_record_payment', function($p) use($params){
                            $p->whereStateTypeAccepted()->whereTypeUser()->where('currency_id', $params->currency_id);
                        });
                    
                })
                ->OrWhereHas('exp_payment', function($q) use($params){
                    $q->whereBetween('date_of_payment', [$params->date_start, $params->date_end])
                        ->whereHas('associated_record_payment', function($p) use($params){
                            $p->whereStateTypeAccepted()->whereTypeUser()->where('currency_id', $params->currency_id);
                        });

                })
                // ->OrWhereHas('sln_payments', function($q) use($params){
                //     $q->whereBetween('date_of_payment', [$params->date_start, $params->date_end])
                //         ->whereHas('associated_record_payment', function($p) use($params){
                //             $p->whereStateTypeAccepted()->whereTypeUser()->where('currency_id', $params->currency_id)
                //                 ->whereNotChanged();
                //         });
                    
                // })
                ->OrWhereHas('pur_payment', function($q) use($params){
                    $q->whereBetween('date_of_payment', [$params->date_start, $params->date_end])
                        ->whereHas('associated_record_payment', function($p) use($params){
                            $p->whereStateTypeAccepted()->whereTypeUser()->where('currency_id', $params->currency_id);
                        });

                })
                ->OrWhereHas('sup_payment', function($q) use($params){
                    $q->whereBetween('date_of_payment', [$params->date_start, $params->date_end])
                        ->whereHas('associated_record_payment', function($p) use($params){
                            $p->whereStateTypeAccepted()->whereTypeUser()->where('currency_id', $params->currency_id);
                        });
                
                })
                ->OrWhereHas('quo_payment', function($q) use($params){
                    $q->whereBetween('date_of_payment', [$params->date_start, $params->date_end])
                        ->whereHas('associated_record_payment', function($p) use($params){
                            $p->whereStateTypeAccepted()->whereTypeUser()->where('currency_id', $params->currency_id)
                                ->whereNotChanged();
                        });

                })
                // ->OrWhereHas('con_payment', function($q) use($params){
                //     $q->whereBetween('date_of_payment', [$params->date_start, $params->date_end])
                //         ->whereHas('associated_record_payment', function($p) use($params){
                //             $p->whereStateTypeAccepted()->whereTypeUser()->where('currency_id', $params->currency_id)
                //                 ->whereNotChanged();
                //         });

                // })
                ->OrWhereHas('inc_payment', function($q) use($params){
                    $q->whereBetween('date_of_payment', [$params->date_start, $params->date_end])
                        ->whereHas('associated_record_payment', function($p) use($params){
                            $p->whereStateTypeAccepted()->whereTypeUser()->where('currency_id', $params->currency_id);
                        });

                })
                ->OrWhereHas('rem_payment', function($q) use($params){
                    $q->whereBetween('date_of_payment', [$params->date_start, $params->date_end])
                        ->whereHas('associated_record_payment', function($p) use($params){
                            $p->whereTypeUser()->where('currency_id', $params->currency_id);
                        });
                })
                ->OrWhereHas('doc_pos_payment', function($q) use($params){
                    $q->whereBetween('date_of_payment', [$params->date_start, $params->date_end])
                        ->whereHas('associated_record_payment', function($p) use($params){
                            $p->whereTypeUser()->where('currency_id', $params->currency_id);
                        });
                });

    }


    public function getDocumentTypeDescription()
    {
        $record = $this->payment->associated_record_payment ?? null;
        $document_type = '';

        if ($this->instance_type === 'purchase') {
            // Para compras: validar document_type
            if ($record && isset($record->document_type) && $record->document_type) {
                $document_type = $record->document_type->description ?? $record->document_type->name ?? '';
            }
        } elseif ($this->instance_type === 'support_document') {
            // Para documentos de soporte: validar type_document
            if ($record && isset($record->type_document) && $record->type_document) {
                $document_type = $record->type_document->description ?? $record->type_document->name ?? '';
            }
        } elseif ($this->instance_type === 'document_pos') {
            // No tocar POS
            $document_type = $record->getDocumentTypeDescription();
        } elseif ($record && isset($record->prefix)) {
            // Otros casos con prefijo
            $document_type = $record->prefix;
        } elseif ($record && isset($record->document_type) && $record->document_type) {
            // Por defecto para documentos normales
            $document_type = $record->document_type->description ?? $record->document_type->name ?? '';
        }

        return $document_type;
    }
 

}