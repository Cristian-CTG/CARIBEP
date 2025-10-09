<?php

namespace Modules\Item\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Tenant\Item;
use App\Models\Tenant\Company;
use App\Models\Tenant\ItemUnitType;
use Illuminate\Routing\Controller;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Modules\Item\Imports\ItemListPriceImport;
use Maatwebsite\Excel\Excel;
use Modules\Item\Exports\ItemExport;
use Carbon\Carbon;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\View;
use Mpdf\Mpdf;
use Mpdf\HTMLParserMode;


class ItemController extends Controller
{

    public function generateBarcode($id)
    {
        $item = Item::findOrFail($id);
        $colour = [150,150,150];
        $generator = new BarcodeGeneratorPNG();
        $temp = tempnam(sys_get_temp_dir(), 'item_barcode');
        file_put_contents($temp, $generator->getBarcode($item->internal_id, $generator::TYPE_CODE_128, 5, 70, $colour));
        $headers = [
            'Content-Type' => 'image/png',
        ];
        return response()->download($temp, "{$item->internal_id}.png", $headers);
    }
    public function showBarcodeLabel(Request $request, $id)
    {
        $item = Item::findOrFail($id);
        $company = Company::active();
        $companyName = $company ? $company->name : 'EMPRESA';

        // Parámetros de tamaño y campos
        $width = $request->input('width', 32); // ancho etiqueta en mm
        $height = $request->input('height', 25); // alto etiqueta en mm
        $pageWidth = $request->input('pageWidth', 100); // ancho de hoja en mm
        $columns = $request->input('columns', 3); // etiquetas por fila
        $gapX = $request->input('gapX', 2); // espacio horizontal entre etiquetas
        $repeat = $request->input('repeat', 1); // cuántas etiquetas imprimir

        $fields = [
            'name' => filter_var($request->input('name', true), FILTER_VALIDATE_BOOLEAN),
            'price' => filter_var($request->input('price', true), FILTER_VALIDATE_BOOLEAN),
            'brand' => filter_var($request->input('brand', true), FILTER_VALIDATE_BOOLEAN),
            'category' => filter_var($request->input('category', false), FILTER_VALIDATE_BOOLEAN),
            'color' => filter_var($request->input('color', false), FILTER_VALIDATE_BOOLEAN),
            'size' => filter_var($request->input('size', false), FILTER_VALIDATE_BOOLEAN),
        ];

        // Solo una fila por PDF
        $rows = 1;
        $pageHeight = $height + 2 * $gapX;

        // Calcular ancho máximo permitido por etiqueta
        $maxWidth = ($pageWidth - ($columns - 1) * $gapX) / $columns;
        $labelWidth = min($width, $maxWidth);


        $html = view('tenant.item.barcode_labels_grid', [
            'width' => $labelWidth,      // puedes dejarlo si lo usas en otros lados
            'labelWidth' => $labelWidth,
            'height' => $height,
            'gapX' => $gapX,
            'columns' => $columns,
            'repeat' => $repeat,
            'item' => $item,
            'companyName' => $companyName,
            'fields' => $fields,
            'pageWidth' => $pageWidth,
            'pageHeight' => $pageHeight,
        ])->render();

        $mpdf = new Mpdf([
            'format' => [(float)$pageWidth, (float)$pageHeight], // ancho hoja x alto calculado
            'unit' => 'mm',
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
        ]);

        $mpdf->WriteHTML($html);

        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $item->name);
        $filename = $safeName.'_barcode.pdf';

        return response($mpdf->Output($filename, 'I'))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    public function generateBarcodes(Request $request)
    {
        // mantener el orden de ids tal como vienen en la query
        $ids = array_values(array_filter(array_map('trim', explode(',', $request->input('ids')))));

        $company = Company::active();
        $companyName = $company ? $company->name : 'EMPRESA';

        $width = $request->input('width', 32);
        $height = $request->input('height', 25);
        $pageWidth = $request->input('pageWidth', 100);
        $columns = $request->input('columns', 3);
        $gapX = $request->input('gapX', 2);

        // puede venir como "14,9,5" o "3" (single)
        $repeatParam = $request->input('repeat', 1);
        $repeatList = array_map('intval', array_map('trim', explode(',', $repeatParam)));

        $fields = [
            'name' => filter_var($request->input('name', true), FILTER_VALIDATE_BOOLEAN),
            'price' => filter_var($request->input('price', true), FILTER_VALIDATE_BOOLEAN),
            'brand' => filter_var($request->input('brand', true), FILTER_VALIDATE_BOOLEAN),
            'category' => filter_var($request->input('category', false), FILTER_VALIDATE_BOOLEAN),
            'color' => filter_var($request->input('color', false), FILTER_VALIDATE_BOOLEAN),
            'size' => filter_var($request->input('size', false), FILTER_VALIDATE_BOOLEAN),
        ];

        // Obtener items y keyBy id para acceso rápido (manteniendo control de existencia)
        $itemsCollection = Item::whereIn('id', $ids)->get()->keyBy(function($i){ return (string)$i->id; });

        // Generar array de items repetidos respetando el orden de $ids y usando $repeatList por índice
        $allItems = [];
        foreach ($ids as $idx => $id) {
            if (!isset($itemsCollection[$id])) {
                // item no encontrado: saltar
                continue;
            }
            $item = $itemsCollection[$id];
            $repeat = isset($repeatList[$idx]) ? (int)$repeatList[$idx] : 1;
            // ignorar repeticiones cero o negativas
            if ($repeat <= 0) {
                continue;
            }
            for ($i = 0; $i < $repeat; $i++) {
                $allItems[] = $item;
            }
        }

        if (count($allItems) === 0) {
            // No hay etiquetas para imprimir (todos con stock 0/negativo o datos inválidos)
            abort(400, 'No hay etiquetas para imprimir (stock 0 o datos inválidos).');
        }

        $total = count($allItems);
        $col = $columns;
        $rows = ceil($total / $col);
        $pageHeight = $height + $gapX + 0.1;

        $html = view('tenant.item.barcode_labels_grid', [
            'width' => $width,
            'height' => $height,
            'gapX' => $gapX,
            'columns' => $columns,
            'repeat' => $total,
            'items' => $allItems,
            'companyName' => $companyName,
            'fields' => $fields,
            'pageWidth' => $pageWidth,
            'pageHeight' => $pageHeight,
        ])->render();

        $mpdf = new Mpdf([
            'format' => [(float)$pageWidth, (float)$pageHeight],
            'unit' => 'mm',
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
        ]);

        $mpdf->WriteHTML($html);

        $filename = 'etiquetas_barcode.pdf';

        return response($mpdf->Output($filename, 'I'))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }


    public function coExport()
    {
        $records = Item::get();

        return (new ItemExport)
                ->records($records)
                ->download('Productos'.Carbon::now().'.xlsx');

    }


    public function importItemPriceLists(Request $request)
    {
        if ($request->hasFile('file')) {
            try {
                $import = new ItemListPriceImport();
                $import->import($request->file('file'), null, Excel::XLSX);
                $data = $import->getData();
                return [
                    'success' => true,
                    'message' =>  __('app.actions.upload.success'),
                    'data' => $data
                ];
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'message' =>  $e->getMessage()
                ];
            }
        }
        return [
            'success' => false,
            'message' =>  __('app.actions.upload.error'),
        ];
    }

}
