<?php
namespace Modules\Purchase\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Purchase\Http\Resources\PurchasePaymentCollection;
use Modules\Purchase\Http\Requests\PurchasePaymentRequest;
use App\Models\Tenant\PaymentMethodType;
use App\Models\Tenant\PurchasePayment;
use App\Models\Tenant\Purchase;
use Modules\Finance\Traits\FinanceTrait; 
use Modules\Finance\Traits\FilePaymentTrait; 
use Illuminate\Support\Facades\DB;
use Modules\Factcolombia1\Models\Tenant\PaymentMethod;

class PurchasePaymentController extends Controller
{
    use FinanceTrait, FilePaymentTrait;

    public function records($purchase_id)
    {
        $records = PurchasePayment::where('purchase_id', $purchase_id)->get();

        return new PurchasePaymentCollection($records);
    }

    public function tables()
    {
        return [
            'payment_method_types' => PaymentMethodType::all(),
            'payment_methods' => PaymentMethod::all(),
            'payment_destinations' => $this->getPaymentDestinations()
        ];
    }

    public function purchase($purchase_id)
    {
        $purchase = Purchase::find($purchase_id);

        $total_paid = collect($purchase->payments)->sum('payment');
        $total = $purchase->total;
        $total_difference = round($total - $total_paid, 2);

        return [
            'number_full' => $purchase->number_full,
            'total_paid' => $total_paid,
            'total' => $total,
            'total_difference' => $total_difference
        ];

    }


    public function store(PurchasePaymentRequest $request)
    {
        $id = $request->input('id');

        DB::connection('tenant')->transaction(function () use ($id, $request) {

            $record = PurchasePayment::firstOrNew(['id' => $id]);
            $record->fill($request->all());
            if ($request->filled('payment_method_id')) {
                $record->payment_method_type_id = null;
            }
            $record->save();

            // Obtener la compra asociada
            $purchase = $record->purchase;
            $is_credit = $purchase->date_of_issue != $purchase->date_of_due;

            $this->createGlobalPayment($record, $request->all(), $is_credit);
            $this->saveFiles($record, $request, 'purchases');

        });

        return [
            'success' => true,
            'message' => ($id)?'Pago editado con éxito':'Pago registrado con éxito'
        ];
    }

    public function destroy($id)
    {
        $item = PurchasePayment::findOrFail($id);
        $item->delete();

        return [
            'success' => true,
            'message' => 'Pago eliminado con éxito'
        ];
    }
 


}
