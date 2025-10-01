<?php

namespace Modules\Factcolombia1\Models\Tenant;

use App\Models\Tenant\DocumentPayment;
use App\Models\Tenant\DocumentPosPayment;
use App\Models\Tenant\PurchasePayment;
use Illuminate\Database\Eloquent\Model;
use Hyn\Tenancy\Traits\UsesTenantConnection;
use Modules\Finance\Models\IncomePayment;
use Modules\Sale\Models\ContractPayment;
use Modules\Sale\Models\QuotationPayment;
use Modules\Sale\Models\RemissionPayment;
use Modules\Purchase\Models\SupportDocumentPayment;

class PaymentMethod extends Model
{
    use UsesTenantConnection;

    protected $table = 'co_payment_methods';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'code',
    ];

    public function document_payments()
    {
        return $this->hasMany(DocumentPayment::class, 'payment_method_id');
    }

    public function purchase_payments()
    {
        return $this->hasMany(PurchasePayment::class, 'payment_method_id');
    }

    public function support_document_payments()
    {
        return $this->hasMany(SupportDocumentPayment::class, 'payment_method_id');
    }

    public function quotation_payments()
    {
        return $this->hasMany(QuotationPayment::class, 'payment_method_id');
    }

    public function contract_payments()
    {
        return $this->hasMany(ContractPayment::class, 'payment_method_id');
    }

    public function income_payments()
    {
        return $this->hasMany(IncomePayment::class, 'payment_method_id');
    }

    public function remission_payments()
    {
        return $this->hasMany(RemissionPayment::class, 'payment_method_id');
    }

    public function document_pos_payments()
    {
        return $this->hasMany(DocumentPosPayment::class, 'payment_method_id');
    }

    public function scopeWhereFilterPayments($query, $params)
    {
        return $query->with([
            'document_payments' => function($q) use($params){
                $q->whereBetween('date_of_payment', [$params->date_start, $params->date_end])
                ->whereHas('associated_record_payment', function($p) use($params){
                    $p->whereStateTypeAccepted()->whereTypeUser()->where('currency_id', $params->currency_id);
                });
            },
            'quotation_payments' => function($q) use($params){
                $q->whereBetween('date_of_payment', [$params->date_start, $params->date_end])
                ->whereHas('associated_record_payment', function($p) use($params){
                    $p->whereStateTypeAccepted()->whereTypeUser()->where('currency_id', $params->currency_id)
                        ->whereNotChanged();
                });
            },
            'purchase_payments' => function($q) use($params){
                $q->whereBetween('date_of_payment', [$params->date_start, $params->date_end])
                ->whereHas('associated_record_payment', function($p) use($params){
                    $p->whereStateTypeAccepted()->whereTypeUser()->where('currency_id', $params->currency_id);
                });
            },
            'support_document_payments' => function($q) use($params){
                $q->whereBetween('date_of_payment', [$params->date_start, $params->date_end])
                ->whereHas('associated_record_payment', function($p) use($params){
                    $p->whereStateTypeAccepted()->whereTypeUser()->where('currency_id', $params->currency_id);
                });
            },
            'income_payments' => function($q) use($params){
                $q->whereBetween('date_of_payment', [$params->date_start, $params->date_end])
                ->whereHas('associated_record_payment', function($p) use($params){
                    $p->whereStateTypeAccepted()->whereTypeUser()->where('currency_id', $params->currency_id);
                });
            },
            'remission_payments' => function($q) use($params){
                $q->whereBetween('date_of_payment', [$params->date_start, $params->date_end])
                ->whereHas('associated_record_payment', function($p) use($params){
                    $p->whereTypeUser()->where('currency_id', $params->currency_id);
                });
            },
            'document_pos_payments' => function($q) use($params){
                $q->whereBetween('date_of_payment', [$params->date_start, $params->date_end])
                ->whereHas('associated_record_payment', function($p) use($params){
                    $p->whereTypeUser()->where('currency_id', $params->currency_id);
                });
            }
        ]);
    }
}
