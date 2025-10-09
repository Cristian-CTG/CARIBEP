<?php

namespace Modules\Finance\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class GlobalPaymentCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->collection->transform(function($row, $key) {

            $data_person = $row->data_person ?? (object)['name' => '', 'number' => ''];
            $document_type = $row->getDocumentTypeDescription();

            // Validar si existe payment
            $payment = $row->payment;

            return [
                'id' => $row->id,
                'destination_description' => $row->destination_description,
                'date_of_payment' => $payment && $payment->date_of_payment ? $payment->date_of_payment->format('Y-m-d') : null,
                'payment_method_type_description' =>
                    $payment && $payment->payment_method_type
                        ? $payment->payment_method_type->description
                        : (
                            $payment && $payment->expense_method_type
                                ? $payment->expense_method_type->description
                                : null
                        ),
                'payment_method_name' => $payment && $payment->payment_method_name ? $payment->payment_method_name : null,
                'reference' => $payment && $payment->reference ? $payment->reference : null,
                'total' => $payment && $payment->payment ? $payment->payment : null,
                'number_full' => $payment && $payment->associated_record_payment ? $payment->associated_record_payment->number_full : null,
                'currency_type_id' => $payment && $payment->associated_record_payment ? $payment->associated_record_payment->currency_type_id : null,
                'document_type_description' => $document_type,
                'person_name' => $data_person->name,
                'person_number' => $data_person->number,
                'instance_type' => $row->instance_type,
                'instance_type_description' => $row->instance_type_description,
            ];
        });
    }


}
