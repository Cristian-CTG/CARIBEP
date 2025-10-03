<?php

namespace Modules\Purchase\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupportDocumentPaymentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->input('id');
        return [
            'date_of_payment' => [
                'date',
                'required',
            ],
            'payment_method_id' => [
                'nullable', 'required_without:payment_method_type_id'
            ],
            'payment_method_type_id' => [
                'nullable', 'required_without:payment_method_id'
            ],
            'payment_destination_id' => [
                'required',
            ],
            'payment' => [
                'required',
            ],
        ];
    }
}