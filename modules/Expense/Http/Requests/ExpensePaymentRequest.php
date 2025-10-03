<?php

namespace Modules\Expense\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExpensePaymentRequest extends FormRequest
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
                'nullable', 'required_without:expense_method_type_id'
            ],
            'expense_method_type_id' => [
                'nullable', 'required_without:payment_method_id'
            ],
            'payment_destination_id' => [
                'required_unless:expense_method_type_id, "1"',
                // 'required',
            ],
            'payment' => [
                'required',
            ],
        ];
    }
}