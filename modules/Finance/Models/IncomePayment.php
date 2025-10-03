<?php

namespace Modules\Finance\Models;

use Modules\Finance\Models\GlobalPayment;
use App\Models\Tenant\PaymentMethodType;
use App\Models\Tenant\CardBrand;
use App\Models\Tenant\ModelTenant;
use Modules\Factcolombia1\Models\Tenant\PaymentMethod;

class IncomePayment extends ModelTenant
{
    protected $with = ['payment_method_type', 'card_brand'];
    public $timestamps = false;

    protected $fillable = [
        'income_id',
        'date_of_payment',
        'payment_method_id',
        'payment_method_type_id',
        'has_card',
        'card_brand_id',
        'reference',
        'change',
        'payment',
    ];

    protected $casts = [
        'date_of_payment' => 'date',
    ];

    public function payment_method_type()
    {
        return $this->belongsTo(PaymentMethodType::class);
    }

    public function payment_method()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }
    
    public function getPaymentMethodNameAttribute()
    {
        if ($this->payment_method_id) {
            return $this->payment_method ? $this->payment_method->name : null;
        }
        if ($this->payment_method_type_id) {
            return $this->payment_method_type ? $this->payment_method_type->description : null;
        }
        return null;
    }

    public function card_brand()
    {
        return $this->belongsTo(CardBrand::class);
    }

    public function global_payment()
    {
        return $this->morphOne(GlobalPayment::class, 'payment');
    }
 
    public function associated_record_payment()
    {
        return $this->belongsTo(Income::class, 'income_id');
    }
}