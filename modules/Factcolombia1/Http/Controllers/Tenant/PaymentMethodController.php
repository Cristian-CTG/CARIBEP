<?php

namespace Modules\Factcolombia1\Http\Controllers\Tenant;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Factcolombia1\Models\Tenant\PaymentMethod;
use Exception;
use Modules\Factcolombia1\Http\Resources\Tenant\PaymentMethodCollection;

class PaymentMethodController extends Controller
{
    public function records(Request $request)
    {
        $records = PaymentMethod::get();
        return new PaymentMethodCollection($records);
    }

    public function record($id)
    {
        $record = PaymentMethod::findOrFail($id);
        return $record;
    }

    public function store(Request $request)
    {
        $id = $request->input('id');
        $record = PaymentMethod::firstOrNew(['id' => $id]);
        $record->fill($request->only(['name', 'code']));
        $record->save();

        return [
            'success' => true,
            'message' => ($id) ? 'Método de pago editado con éxito' : 'Método de pago registrado con éxito',
        ];
    }

    public function destroy($id)
    {
        try {
            $record = PaymentMethod::findOrFail($id);
            $record->delete();

            return [
                'success' => true,
                'message' => 'Método de pago eliminado con éxito'
            ];
        } catch (Exception $e) {
            return ($e->getCode() == '23000')
                ? ['success' => false, 'message' => "El Método de pago está siendo usado por otros registros, no puede eliminar"]
                : ['success' => false, 'message' => "Error inesperado, no se pudo eliminar el Método de pago"];
        }
    }
}