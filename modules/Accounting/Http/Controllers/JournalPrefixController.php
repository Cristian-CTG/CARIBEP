<?php

namespace Modules\Accounting\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Accounting\Models\JournalPrefix;
use Illuminate\Validation\Rule;

/*
 * Class JournalPrefixController
 * Controlador para gestionar los prefijos de asientos contables
 */
class JournalPrefixController extends Controller
{
    public function index()
    {
        return JournalPrefix::where('modifiable',1)->get();
    }

    public function store(Request $request)
    {
        
        $request->validate([
            'prefix' => 'required|string',
            'description' => 'required|string',
            'modifiable' => 'required|boolean',
        ]);

        $prefixFound = JournalPrefix::where('prefix',$request->prefix)->first();

        if($prefixFound){
            return response()->json(['message' => 'Prefijo ya existe']);
        }
        
        return JournalPrefix::create($request->all());
    }

    public function show($id)
    {
        return JournalPrefix::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $prefix = JournalPrefix::findOrFail($id);
        if (!$prefix->modifiable) {
            return response()->json(['message' => 'Este prefijo no es modificable'], 403);
        }

        $prefix->update($request->all());
        return $prefix;
    }

    /**
     * Elimina un prefijo de asiento contable.
     *
     * @param int $id Identificador del prefijo a eliminar.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $prefix = JournalPrefix::findOrFail($id);
        if (!$prefix->modifiable) {
            return response()->json(['message' => 'Este prefijo no se puede eliminar'], 403);
        }

        $prefix->delete();
        return response()->json(['message' => 'Eliminado correctamente']);
    }
}
