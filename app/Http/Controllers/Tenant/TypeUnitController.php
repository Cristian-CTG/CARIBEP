<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\Tenant\TypeUnitResource;
use App\Http\Resources\Tenant\TypeUnitCollection;
use App\Http\Requests\Tenant\TypeUnitRequest;
use Illuminate\Http\Request;
use App\Models\Tenant\DocumentItem;
use Modules\Factcolombia1\Models\Tenant\TypeUnit;
use Exception;

class TypeUnitController extends Controller
{
    public function records()
    {
        $records = TypeUnit::all();
        return new TypeUnitCollection($records);
    }

    public function record($id)
    {
        $record = TypeUnit::findOrFail($id);
        return new TypeUnitResource($record);
    }

    public function store(TypeUnitRequest $request)
    {
        $id = $request->input('id');
        $type_unit = TypeUnit::firstOrNew(['id' => $id]);
        $type_unit->fill($request->only(['name', 'code']));
        $type_unit->save();

        return [
            'success' => true,
            'message' => ($id) ? 'Unidad editada con éxito' : 'Unidad registrada con éxito'
        ];
    }

    public function destroy($id)
    {
        try {
            $record = TypeUnit::findOrFail($id);

            $record->delete();

            return [
                'success' => true,
                'message' => 'Unidad eliminada con éxito'
            ];
        } catch (Exception $e) {
            return ($e->getCode() == '23000')
                ? ['success' => false, 'message' => 'La unidad está siendo usada por otros registros, no puede eliminar']
                : ['success' => false, 'message' => 'Error inesperado, no se pudo eliminar la unidad'];
        }
    }
}