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

        // Recibe parámetros de tamaño y campos
        $width = $request->input('width');
        $height = $request->input('height');
        $fields = [
            'name' => filter_var($request->input('name', true), FILTER_VALIDATE_BOOLEAN),
            'price' => filter_var($request->input('price', true), FILTER_VALIDATE_BOOLEAN),
            'brand' => filter_var($request->input('brand', true), FILTER_VALIDATE_BOOLEAN),
            'category' => filter_var($request->input('category', false), FILTER_VALIDATE_BOOLEAN),
            'color' => filter_var($request->input('color', false), FILTER_VALIDATE_BOOLEAN),
            'size' => filter_var($request->input('size', false), FILTER_VALIDATE_BOOLEAN),
        ];

        $generator = new BarcodeGeneratorPNG();
        $usableHeight = ($height ?? 25) * 0.32; // 32% de la altura para el código de barras
        $usableWidth = ($width ?? 32) * 0.80;   // 80% del ancho para el código de barras

        $codeLength = strlen($item->internal_id);

        // Calcula el ancho de barra para que el código de barras no se desborde
        $barcodeWidthFactor = $usableWidth / $codeLength / 2.2;
        $barcodeWidthFactor = max(min($barcodeWidthFactor, 2), 0.5); // Limita el rango

        // Altura en píxeles, proporcional a la altura de la etiqueta (1mm ≈ 3.78px)
        $barcodeHeightPx = intval($usableHeight * 3.78);
        $barcodeHeightPx = max(min($barcodeHeightPx, 60), 12); // Limita el rango

        $barcodeData = $generator->getBarcode(
            $item->internal_id,
            $generator::TYPE_CODE_128,
            $barcodeWidthFactor,
            $barcodeHeightPx
        );
        $barcodeBase64 = base64_encode($barcodeData);

        $html = view('tenant.item.barcode_label', compact('item', 'companyName', 'barcodeBase64', 'fields', 'width', 'height'))->render();

        $mpdf = new Mpdf([
            'format' => [(float)$width, (float)$height],
            'unit' => 'mm',
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
        ]);
        $mpdf->WriteHTML($html);
        // Sanitiza el nombre del producto para el archivo
        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $item->name);
        $filename = $safeName.'_barcode.pdf';

        return response($mpdf->Output($filename, 'S'))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    public function generateBarcodes(Request $request)
    {
        $ids = explode(',', $request->input('ids'));
        $company = Company::active();
        $companyName = $company ? $company->name : 'EMPRESA';

        // Recibe parámetros de tamaño y campos
        $width = $request->input('width', 32);
        $height = $request->input('height', 25);
        $fields = [
            'name' => filter_var($request->input('name', true), FILTER_VALIDATE_BOOLEAN),
            'price' => filter_var($request->input('price', true), FILTER_VALIDATE_BOOLEAN),
            'brand' => filter_var($request->input('brand', true), FILTER_VALIDATE_BOOLEAN),
            'category' => filter_var($request->input('category', false), FILTER_VALIDATE_BOOLEAN),
            'color' => filter_var($request->input('color', false), FILTER_VALIDATE_BOOLEAN),
            'size' => filter_var($request->input('size', false), FILTER_VALIDATE_BOOLEAN),
        ];

        $mpdf = new Mpdf([
            'format' => [(float)$width, (float)$height], // tamaño etiqueta en mm
            'unit' => 'mm',
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
        ]);

        $first = true;
        foreach ($ids as $id) {
            $item = Item::find($id);
            if (!$item) continue;

            $generator = new BarcodeGeneratorPNG();

            // Calcula el tamaño del código de barras igual que en showBarcodeLabel
            $usableHeight = $height * 0.32;
            $usableWidth = $width * 0.80;
            $codeLength = strlen($item->internal_id);
            $barcodeWidthFactor = $usableWidth / $codeLength / 2.2;
            $barcodeWidthFactor = max(min($barcodeWidthFactor, 2), 0.5);
            $barcodeHeightPx = intval($usableHeight * 3.78);
            $barcodeHeightPx = max(min($barcodeHeightPx, 60), 12);

            $barcodeData = $generator->getBarcode(
                $item->internal_id,
                $generator::TYPE_CODE_128,
                $barcodeWidthFactor,
                $barcodeHeightPx
            );
            $barcodeBase64 = base64_encode($barcodeData);

            $html = view('tenant.item.barcode_label', compact('item', 'companyName', 'barcodeBase64', 'fields', 'width', 'height'))->render();

            if (!$first) {
                $mpdf->AddPage();
            }
            $mpdf->WriteHTML($html);
            $first = false;
        }

        return response($mpdf->Output('etiquetas.pdf', 'S'))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="Labels_barcode.pdf"');
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
