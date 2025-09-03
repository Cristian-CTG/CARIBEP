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
        $finalWidth = 378;
        $finalHeight = 295;
        $padding = 18;
        $lineSpacing = 12;

        $image = imagecreatetruecolor($finalWidth, $finalHeight);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefill($image, 0, 0, $white);

        // Función para ajustar el tamaño de fuente
        $fitFontSize = function($texts, $maxWidth, $fontPath, $maxFontSize, $minFontSize = 15) {
            // $texts puede ser array o string
            if (!is_array($texts)) $texts = [$texts];
            for ($size = $maxFontSize; $size >= $minFontSize; $size--) {
                $totalWidth = 0;
                foreach ($texts as $text) {
                    $bbox = imagettfbbox($size, 0, $fontPath, $text);
                    $totalWidth += abs($bbox[2] - $bbox[0]);
                }
                // Suma 8px de espacio entre textos si hay más de uno
                $totalWidth += (count($texts) - 1) * 8;
                if ($totalWidth <= $maxWidth) return $size;
            }
            return $minFontSize;
        };

        // Título (empresa)
        $title = $companyName;
        $titleFontSize = $fitFontSize($title, $finalWidth - 30, $fontPath, 22, 15);
        $bboxTitle = imagettfbbox($titleFontSize, 0, $fontPath, $title);
        $titleWidth = abs($bboxTitle[2] - $bboxTitle[0]);
        $titleHeight = abs($bboxTitle[7] - $bboxTitle[1]);
        $titleX = ($finalWidth - $titleWidth) / 2;
        $y = $padding + $titleHeight;
        imagettftext($image, $titleFontSize, 0, $titleX, $padding + $titleHeight, $black, $fontPath, $title);
        $y += $titleHeight + $lineSpacing;

        // Bloque de detalles: nombre, categoría, marca, color, talla
        $detailLines = [];
        $mainLine = "{$item->name}";
        if ($item->category && $item->category->name) {
            $mainLine .= " | {$item->category->name}";
        }
        if ($item->brand && $item->brand->name) {
            $mainLine .= " | {$item->brand->name}";
        }
        // Divide en líneas si es necesario
        $maxLineWidth = $finalWidth - 30;
        $words = explode(' ', $mainLine);
        $currentLine = '';
        foreach ($words as $word) {
            $testLine = $currentLine ? $currentLine . ' ' . $word : $word;
            $bbox = imagettfbbox(18, 0, $fontPath, $testLine);
            $testWidth = abs($bbox[2] - $bbox[0]);
            if ($testWidth > $maxLineWidth && $currentLine) {
                $detailLines[] = $currentLine;
                $currentLine = $word;
            } else {
                $currentLine = $testLine;
            }
        }
        if ($currentLine) $detailLines[] = $currentLine;

        // Color y talla en una línea aparte
        $secondLine = '';
        if ($item->color && $item->color->name) {
            $secondLine .= "{$item->color->name}";
        }
        if ($item->size && $item->size->name) {
            $secondLine .= ($secondLine ? " | " : "") . "{$item->size->name}";
        }
        if ($secondLine) $detailLines[] = $secondLine;

        // Calcula el tamaño de fuente máximo para todas las líneas de detalles
        $detailFontSize = $fitFontSize($detailLines, $maxLineWidth, $fontPath, 18, 15);

        // Dibuja cada línea de detalles con el mismo tamaño
        foreach ($detailLines as $line) {
            $bboxLine = imagettfbbox($detailFontSize, 0, $fontPath, $line);
            $lineWidth = abs($bboxLine[2] - $bboxLine[0]);
            $lineHeight = abs($bboxLine[7] - $bboxLine[1]);
            $lineX = ($finalWidth - $lineWidth) / 2;
            imagettftext($image, $detailFontSize, 0, $lineX, $y + $lineHeight, $black, $fontPath, $line);
            $y += $lineHeight + $lineSpacing;
        }

        // Código de barras
        $generator = new BarcodeGeneratorPNG();
        $barcodeData = $generator->getBarcode($item->internal_id, $generator::TYPE_CODE_128, 2, 40);
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

        // Código (ajuste de tamaño)
        $codeText = $item->internal_id;
        $fontSizeCode = $fitFontSize($codeText, $maxLineWidth, $fontPath, 16, 15);
        $bboxCode = imagettfbbox($fontSizeCode, 0, $fontPath, $codeText);
        $codeWidth = abs($bboxCode[2] - $bboxCode[0]);
        $codeHeight = abs($bboxCode[7] - $bboxCode[1]);
        $codeX = ($finalWidth - $codeWidth) / 2;
        imagettftext($image, $fontSizeCode, 0, $codeX, $y + $codeHeight, $black, $fontPath, $codeText);
        $y += $codeHeight + $lineSpacing;

        // Precio: símbolo y número separados, tamaño ajustado
        $currencySymbol = $item->currency_type ? $item->currency_type->symbol : 'S/';
        $priceNumber = number_format($item->sale_unit_price, 2);
        $priceFontSize = $fitFontSize([$currencySymbol, $priceNumber], $maxLineWidth, $fontPath, 18, 15);

        // Calcula posiciones
        $bboxSymbol = imagettfbbox($priceFontSize, 0, $fontPath, $currencySymbol);
        $symbolWidth = abs($bboxSymbol[2] - $bboxSymbol[0]);
        $symbolHeight = abs($bboxSymbol[7] - $bboxSymbol[1]);
        $bboxNumber = imagettfbbox($priceFontSize, 0, $fontPath, $priceNumber);
        $numberWidth = abs($bboxNumber[2] - $bboxNumber[0]);
        $numberHeight = abs($bboxNumber[7] - $bboxNumber[1]);
        $space = 8; // espacio fijo entre símbolo y número
        $totalWidth = $symbolWidth + $space + $numberWidth;
        $priceX = ($finalWidth - $totalWidth) / 2;
        $priceY = $y + max($symbolHeight, $numberHeight);

        // Dibuja símbolo y número
        imagettftext($image, $priceFontSize, 0, $priceX, $priceY, $black, $fontPath, $currencySymbol);
        imagettftext($image, $priceFontSize, 0, $priceX + $symbolWidth + $space, $priceY, $black, $fontPath, $priceNumber);

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
