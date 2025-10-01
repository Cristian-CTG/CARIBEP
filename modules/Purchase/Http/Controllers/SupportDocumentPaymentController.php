<?php

namespace Modules\Purchase\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Purchase\Http\Resources\SupportDocumentPaymentCollection;
use Modules\Purchase\Http\Requests\SupportDocumentPaymentRequest;
use Illuminate\Http\Request;
use App\Models\Tenant\PaymentMethodType;
use Modules\Purchase\Models\SupportDocumentPayment;
use Modules\Purchase\Models\SupportDocument;
use Modules\Finance\Traits\FinanceTrait; 
use Modules\Finance\Traits\FilePaymentTrait;
use Illuminate\Support\Facades\DB;
use Modules\Factcolombia1\Models\Tenant\PaymentMethod;

class SupportDocumentPaymentController extends Controller
{
    use FinanceTrait, FilePaymentTrait;
    public function records($support_document_id)
    {
        $records = SupportDocumentPayment::where('support_document_id', $support_document_id)->get();
        
        return new SupportDocumentPaymentCollection($records);
    }

    public function tables()
    {
        return [
            'payment_method_types' => PaymentMethodType::all(),
            'payment_methods' => PaymentMethod::all(),
            'payment_destinations' => $this->getPaymentDestinations(),
        ];
    }

    public function support_document($support_document_id)
    {
        $doc = SupportDocument::find($support_document_id);
        $total_paid = $doc->payments->sum('payment');
        $total = $doc->total;
        $total_difference = round($total - $total_paid, 2);

        return [
            'number_full' => $doc->number_full,
            'total_paid' => $total_paid,
            'total' => $total,
            'total_difference' => $total_difference
        ];
    }

    public function store(SupportDocumentPaymentRequest $request)
    {
        $id = $request->input('id');

        DB::connection('tenant')->transaction(function () use ($id, $request) {
            
            $record = SupportDocumentPayment::firstOrNew(['id' => $id]);
            $record->fill($request->all());
            if ($request->filled('payment_method_id')) {
                $record->payment_method_type_id = null;
            }
            $record->save();
            $this->createGlobalPayment($record, $request->all());
            $this->saveFiles($record, $request, 'support-documents');
        });

        return [
            'success' => true,
            'message' => ($id) ? 'Pago editado con éxito' : 'Pago registrado con éxito'
        ];
    }

    public function destroy($id)
    {
        $item = SupportDocumentPayment::findOrFail($id);
        $item->delete();

        return [
            'success' => true,
            'message' => 'Pago eliminado con éxito'
        ];
    }
}