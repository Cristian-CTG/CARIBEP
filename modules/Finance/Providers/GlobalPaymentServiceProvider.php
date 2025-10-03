<?php

namespace Modules\Finance\Providers;

use App\Models\Tenant\{
    SaleNotePayment,
    DocumentPayment,
    PurchasePayment,
};  
use Modules\Sale\Models\QuotationPayment;
use Modules\Sale\Models\ContractPayment;
use Modules\Expense\Models\ExpensePayment;
use Modules\Finance\Models\IncomePayment;
use Modules\Sale\Models\RemissionPayment;
use Modules\Purchase\Models\SupportDocumentPayment;

use Illuminate\Support\ServiceProvider;

class GlobalPaymentServiceProvider extends ServiceProvider
{

    public function register()
    {
    }
    
    public function boot()
    {

        $this->deletingPayment(SaleNotePayment::class);
        $this->deletingPayment(DocumentPayment::class);
        $this->deletingPayment(PurchasePayment::class);
        $this->deletingPayment(QuotationPayment::class);
        $this->deletingPayment(ExpensePayment::class);
        $this->deletingPayment(ContractPayment::class);
        $this->deletingPayment(IncomePayment::class);
        $this->deletingPayment(RemissionPayment::class);
        $this->deletingPayment(SupportDocumentPayment::class);

        $this->paymentsPurchases();
        $this->paymentsSupportDocuments();

    }

    private function deletingPayment($model)
    {

        $model::deleting(function ($record) {
            
            if($record->global_payment){
                $record->global_payment()->delete();
            }

            if($record->payment_file){
                $record->payment_file()->delete();
            }

        });

    }

    private function paymentsSupportDocuments()
    {
        SupportDocumentPayment::created(function ($support_document_payment) {
            $this->transaction_payment($support_document_payment);
        });

        SupportDocumentPayment::deleted(function ($support_document_payment) {
            $this->transaction_payment($support_document_payment);
        });
    }
 

    private function paymentsPurchases()
    {

        PurchasePayment::created(function ($purchase_payment) {
            $this->transaction_payment($purchase_payment);
        });
 
        PurchasePayment::deleted(function ($purchase_payment) {
            $this->transaction_payment($purchase_payment);
        });
        
    }
 
    private function transaction_payment($payment)
    {
        // Detecta el documento relacionado
        if ($payment instanceof PurchasePayment) {
            $document = $payment->purchase;
        } elseif ($payment instanceof SupportDocumentPayment) {
            $document = $payment->support_document;
        } else {
            $document = null;
        }

        if ($document && $document->total !== null) {
            $total_payments = $document->payments->sum('payment');
            $balance = $document->total - $total_payments;

            $document->total_canceled = $balance <= 0;
            $document->save();
        }
    }
 
}
