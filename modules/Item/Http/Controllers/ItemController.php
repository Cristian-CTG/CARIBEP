<?php

namespace Modules\Item\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Tenant\Item;
use App\Models\Tenant\ItemUnitType;
use Illuminate\Routing\Controller;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Modules\Item\Imports\ItemListPriceImport;
use Maatwebsite\Excel\Excel;
use Modules\Item\Exports\ItemExport;
use Carbon\Carbon;
use Intervention\Image\Facades\Image;
use Mpdf\Mpdf;


class ItemController extends Controller
{

    public function generateBarcode($id)
    {
        $item = Item::findOrFail($id);

        $company = \App\Models\Tenant\Company::active();
        $companyName = $company ? $company->name : 'EMPRESA';

        $image = $this->buildBarcodeImage($item, $companyName);

        ob_clean();
        header('Content-Type: image/png');
        header('Content-Disposition: attachment; filename="barcode_'.$item->internal_id.'.png"');
        imagepng($image);
        imagedestroy($image);
        exit;
    }

    public function generateBarcodes(Request $request)
    {
        $ids = explode(',', $request->input('ids'));
        $company = \App\Models\Tenant\Company::active();
        $companyName = $company ? $company->name : 'EMPRESA';

        $mpdf = new Mpdf([
            'format' => [378, 295], // tamaño etiqueta en px
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
        ]);

        foreach ($ids as $id) {
            $item = Item::find($id);
            if (!$item) continue;
            $image = $this->buildBarcodeImage($item, $companyName);
            $tmpPath = tempnam(sys_get_temp_dir(), 'barcode_') . '.png';
            imagepng($image, $tmpPath);
            imagedestroy($image);

            $mpdf->AddPage();
            $mpdf->Image($tmpPath, 0, 0, 378, 295, 'png');
            unlink($tmpPath);
        }

        $mpdf->Output('etiquetas.pdf', 'D');
        exit;
    }
    
    private function buildBarcodeImage($item, $companyName)
    {
        $fontPath = public_path('fonts/LiberationSans-Regular.ttf');
        $fontSize = 18;         // Texto general más grande
        $fontSizeTitle = 22;    // Título más grande
        $fontSizeCode = 16;     // Código más grande
        $fontSizePrice = 18;    // Precio más grande

        // Tamaño fijo de etiqueta: 32x25 mm (378x295 px a 300 DPI)
        $finalWidth = 378;
        $finalHeight = 295;

        $padding = 18;           // Más padding
        $lineSpacing = 14;       // Más espacio entre líneas

        $image = imagecreatetruecolor($finalWidth, $finalHeight);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefill($image, 0, 0, $white);

        $title = $companyName;
        $mainLine = "{$item->name}";
        if ($item->category && $item->category->name) {
            $mainLine .= " | {$item->category->name}";
        }
        if ($item->brand && $item->brand->name) {
            $mainLine .= " | {$item->brand->name}";
        }

        $secondLine = '';
        if ($item->color && $item->color->name) {
            $secondLine .= "{$item->color->name}";
        }
        if ($item->size && $item->size->name) {
            $secondLine .= ($secondLine ? " | " : "") . "{$item->size->name}";
        }

        // Título en negrita sutil
        $bboxTitle = imagettfbbox($fontSizeTitle, 0, $fontPath, $title);
        $titleWidth = abs($bboxTitle[2] - $bboxTitle[0]);
        $titleHeight = abs($bboxTitle[7] - $bboxTitle[1]);
        $titleX = ($finalWidth - $titleWidth) / 2;
        $y = $padding + $titleHeight;
        for ($i = 0; $i <= 1; $i++) {
            imagettftext($image, $fontSizeTitle, 0, $titleX + $i, $padding + $titleHeight, $black, $fontPath, $title);
        }
        $y += $titleHeight + $lineSpacing;

        // Línea principal
        $bboxMain = imagettfbbox($fontSize, 0, $fontPath, $mainLine);
        $mainWidth = abs($bboxMain[2] - $bboxMain[0]);
        $mainHeight = abs($bboxMain[7] - $bboxMain[1]);
        $mainX = ($finalWidth - $mainWidth) / 2;
        imagettftext($image, $fontSize, 0, $mainX, $y + $mainHeight, $black, $fontPath, $mainLine);
        $y += $mainHeight + $lineSpacing;

        // Segunda línea
        $bboxSecond = imagettfbbox($fontSize, 0, $fontPath, $secondLine);
        $secondWidth = abs($bboxSecond[2] - $bboxSecond[0]);
        $secondHeight = abs($bboxSecond[7] - $bboxSecond[1]);
        $secondX = ($finalWidth - $secondWidth) / 2;
        imagettftext($image, $fontSize, 0, $secondX, $y + $secondHeight, $black, $fontPath, $secondLine);
        $y += $secondHeight + $lineSpacing;

        // Código de barras
        $generator = new BarcodeGeneratorPNG();
        $barcodeData = $generator->getBarcode($item->internal_id, $generator::TYPE_CODE_128, 2, 40); // tamaño adecuado
        $barcodeImg = imagecreatefromstring($barcodeData);
        $barcodeWidth = imagesx($barcodeImg);
        $barcodeHeight = imagesy($barcodeImg);
        imagecopy(
            $image,
            $barcodeImg,
            ($finalWidth - $barcodeWidth) / 2,
            $y,
            0,
            0,
            $barcodeWidth,
            $barcodeHeight
        );
        $y += $barcodeHeight + $lineSpacing;
        imagedestroy($barcodeImg);

        // Código
        $codeText = $item->internal_id;
        $bboxCode = imagettfbbox($fontSizeCode, 0, $fontPath, $codeText);
        $codeWidth = abs($bboxCode[2] - $bboxCode[0]);
        $codeHeight = abs($bboxCode[7] - $bboxCode[1]);
        $codeX = ($finalWidth - $codeWidth) / 2;
        imagettftext($image, $fontSizeCode, 0, $codeX, $y + $codeHeight, $black, $fontPath, $codeText);
        $y += $codeHeight + $lineSpacing;

        // Precio
        $currencySymbol = $item->currency_type ? $item->currency_type->symbol : 'S/';
        $priceNumber = number_format($item->sale_unit_price, 2);
        $bboxSymbol = imagettfbbox($fontSizePrice, 0, $fontPath, $currencySymbol);
        $symbolWidth = abs($bboxSymbol[2] - $bboxSymbol[0]);
        $bboxNumber = imagettfbbox($fontSizePrice, 0, $fontPath, $priceNumber);
        $numberWidth = abs($bboxNumber[2] - $bboxNumber[0]);
        $priceWidth = $symbolWidth + 8 + $numberWidth;
        $priceX = ($finalWidth - $priceWidth) / 2;
        for ($i = 0; $i < 2; $i++) {
            imagettftext($image, $fontSizePrice, 0, $priceX + $i, $y + $fontSizePrice, $black, $fontPath, $currencySymbol);
            imagettftext($image, $fontSizePrice, 0, $priceX + $symbolWidth + 8 + $i, $y + $fontSizePrice, $black, $fontPath, $priceNumber);
        }

        return $image;
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
