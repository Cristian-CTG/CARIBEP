<?php

namespace Modules\Inventory\Imports;

use Maatwebsite\Excel\Concerns\{
    WithChunkReading,
    WithHeadingRow,
    WithValidation,
    ToModel
};
use Modules\Inventory\Http\Controllers\InventoryController;
use Modules\Inventory\Http\Requests\InventoryRequest;
use App\Models\Tenant\Item;
use Modules\Inventory\Models\Warehouse;

class TransactionMassiveImport implements ToModel, WithValidation, WithHeadingRow, WithChunkReading
{

    public function headingRow(): int {
        return 1;
    }

    public function chunkSize(): int {
        return 500;
    }

    public function rules(): array {
        return [
            'codigo_interno' => 'required|exists:tenant.items,internal_id',
            'tipo_transaccion' => 'required|exists:tenant.inventory_transactions,id',
        ];
    }

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model
    */
    public function model(array $row) {
        $item = Item::where('internal_id', $row['codigo_interno'])->first();

        if (!$item) {
            throw new \Exception('Item no encontrado para el código: ' . $row['codigo_interno']);
        }

        $item_id = $item->id;
        $inventory_transaction_id = $row['tipo_transaccion']; // Usar el del Excel

        // Obtener todos los almacenes
        $warehouses = Warehouse::all()->keyBy('id');

        // Procesar cada columna Stock (N)
        foreach ($row as $key => $value) {
            // Verificar si la columna es del formato "stock_1", "stock_2", etc.
            if (preg_match('/^stock_(\d+)$/i', $key, $matches) && $value !== '' && $value !== null && is_numeric($value) && $value >= 0) {
                $warehouse_id = $matches[1];
                $quantity = (float) $value;

                // Verificar que el almacén existe
                if (!$warehouses->has($warehouse_id)) {
                    throw new \Exception("Almacén con ID {$warehouse_id} no encontrado");
                }

                // Crear la request para el ingreso
                $request = new InventoryRequest();
                $request->merge([
                    "id" => null,
                    "item_id" => $item_id,
                    "warehouse_id" => $warehouse_id,
                    "inventory_transaction_id" => $inventory_transaction_id, // Usar el del Excel
                    "quantity" => $quantity,
                    "type" => "input",
                    "lot_code" => null,
                    "lots_enabled" => false,
                    "series_enabled" => false,
                    "lots" => [],
                    "date_of_due" => null,
                ]);

                // Ejecutar la transacción
                $result = app(InventoryController::class)->store_transaction($request);

                if (!$result['success']) {
                    throw new \Exception("Error en almacén {$warehouse_id}: " . $result['message']);
                }
            }
        }

        return null;
    }
}