<?php

namespace Modules\Factcolombia1\Http\Controllers\Tenant;

use Illuminate\Http\Request;
use Modules\Factcolombia1\Http\Resources\Tenant\AdvancedConfigurationResource;
use App\Models\Tenant\Item;
use App\Http\Controllers\Controller;
use Modules\Factcolombia1\Models\TenantService\AdvancedConfiguration;
use Modules\Factcolombia1\Http\Requests\Tenant\AdvancedConfigurationRequest;
use App\Models\Tenant\Document;
use Illuminate\Support\Facades\Log;
use Modules\Factcolombia1\Models\Tenant\Tax;


class AdvancedConfigurationController extends Controller
{

    public function index()
    {
        return view('factcolombia1::advanced-configuration.index');
    }

    public function record()
    {
        $record = new AdvancedConfigurationResource(AdvancedConfiguration::firstOrFail());
        return  $record;
    }

    public function getTaxes()
    {
        $taxes = Tax::where('is_retention', false)->get();
        
        return [
            'success' => true,
            'data' => $taxes
        ];
    }

    public function store(AdvancedConfigurationRequest $request) {

        $id = $request->input('id');

        $record = AdvancedConfiguration::find($id);
        $record->fill($request->all());
        $record->save();

        return [
            'success' => true,
            'message' => 'Configuración actualizada'
        ];
    }


    public function deleteDocumentByResolution(Request $request)
    {
        $records = Document::where('type_document_id', $request->id)->get();

        if ($records->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No se ha encontrado registros'
            ];
        }

        $ids = $records->pluck('id')->toArray();
        Log::info('Deleted records with IDs: ' . implode(', ', $ids));

        try {
            Document::where('type_document_id', $request->id)->delete();
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'message' => 'No se han eliminado registros'
            ];
        }

        return [
            'success' => true,
            'data' => $records,
            'message' => 'Han sido eliminado los documentos'
        ];
    }

    public function generateDiscountCode(Request $request)
    {
        // Verifica si el usuario es admin
        if (!$request->user() || $request->user()->type !== 'admin') {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $record = AdvancedConfiguration::firstOrFail();
        // Genera un código aleatorio de 8 caracteres
        $code = strtoupper(bin2hex(random_bytes(4)));

        $record->discount_code = $code;
        $record->save();

        return [
            'success' => true,
            'discount_code' => $code,
            'message' => 'Código generado correctamente'
        ];
    }
}
