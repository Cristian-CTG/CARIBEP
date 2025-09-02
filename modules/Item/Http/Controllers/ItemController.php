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
    
    private function buildBarcodeImage($item, $companyName)
    {
        $fontPath = public_path('fonts/LiberationSans-Regular.ttf');
        $fontSize = 14;
        $fontSizeTitle = 21;
        $fontSizeCode = 16;
        $fontSizePrice = 16;
        $padding = 20;
        $lineSpacing = 16;

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

        $bboxTitle = imagettfbbox($fontSizeTitle, 0, $fontPath, $title);
        $titleWidth = abs($bboxTitle[2] - $bboxTitle[0]);
        $titleHeight = abs($bboxTitle[7] - $bboxTitle[1]);

        $bboxMain = imagettfbbox($fontSize, 0, $fontPath, $mainLine);
        $mainWidth = abs($bboxMain[2] - $bboxMain[0]);
        $mainHeight = abs($bboxMain[7] - $bboxMain[1]);

        $bboxSecond = imagettfbbox($fontSize, 0, $fontPath, $secondLine);
        $secondWidth = abs($bboxSecond[2] - $bboxSecond[0]);
        $secondHeight = abs($bboxSecond[7] - $bboxSecond[1]);

        $maxTextWidth = max($titleWidth, $mainWidth, $secondWidth);

        $generator = new BarcodeGeneratorPNG();
        $barcodeData = $generator->getBarcode($item->internal_id, $generator::TYPE_CODE_128, 3, 60);
        $barcodeImg = imagecreatefromstring($barcodeData);
        $barcodeWidth = imagesx($barcodeImg);
        $barcodeHeight = imagesy($barcodeImg);

        $codeText = $item->internal_id;
        $bboxCode = imagettfbbox($fontSizeCode, 0, $fontPath, $codeText);
        $codeWidth = abs($bboxCode[2] - $bboxCode[0]);
        $codeHeight = abs($bboxCode[7] - $bboxCode[1]);

        $currencySymbol = $item->currency_type ? $item->currency_type->symbol : 'S/';
        $priceNumber = number_format($item->sale_unit_price, 2);

        $bboxSymbol = imagettfbbox($fontSizePrice, 0, $fontPath, $currencySymbol);
        $symbolWidth = abs($bboxSymbol[2] - $bboxSymbol[0]);
        $symbolHeight = abs($bboxSymbol[7] - $bboxSymbol[1]);

        $bboxNumber = imagettfbbox($fontSizePrice, 0, $fontPath, $priceNumber);
        $numberWidth = abs($bboxNumber[2] - $bboxNumber[0]);
        $numberHeight = abs($bboxNumber[7] - $bboxNumber[1]);

        $priceWidth = $symbolWidth + 8 + $numberWidth;
        $priceHeight = max($symbolHeight, $numberHeight);

        $finalWidth = max($barcodeWidth, $maxTextWidth, $codeWidth, $priceWidth) + $padding * 2;
        $finalHeight = $padding + $titleHeight + $lineSpacing
            + $mainHeight + $lineSpacing
            + $secondHeight + $lineSpacing
            + $barcodeHeight + $lineSpacing
            + $codeHeight + $lineSpacing
            + $priceHeight + $padding;

        $image = imagecreatetruecolor($finalWidth, $finalHeight);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefill($image, 0, 0, $white);

        $titleX = ($finalWidth - $titleWidth) / 2;
        $y = $padding + $titleHeight;
        for ($i = 0; $i <= 1; $i++) {
            for ($j = 0; $j <= 1; $j++) {
                imagettftext($image, $fontSizeTitle, 0, $titleX + $i, $padding + $titleHeight + $j, $black, $fontPath, $title);
            }
        }

        $mainX = ($finalWidth - $mainWidth) / 2;
        imagettftext($image, $fontSize, 0, $mainX, $y + $lineSpacing + $mainHeight, $black, $fontPath, $mainLine);
        $y += $mainHeight + $lineSpacing * 2;

        $secondX = ($finalWidth - $secondWidth) / 2;
        imagettftext($image, $fontSize, 0, $secondX, $y + $secondHeight, $black, $fontPath, $secondLine);
        $y += $secondHeight + $lineSpacing;

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

        $codeX = ($finalWidth - $codeWidth) / 2;
        imagettftext($image, $fontSizeCode, 0, $codeX, $y + $codeHeight, $black, $fontPath, $codeText);
        $y += $codeHeight + $lineSpacing;

        $priceX = ($finalWidth - $priceWidth) / 2;
        for ($i = 0; $i < 2; $i++) {
            imagettftext($image, $fontSizePrice, 0, $priceX + $i, $y + $priceHeight, $black, $fontPath, $currencySymbol);
            imagettftext($image, $fontSizePrice, 0, $priceX + $symbolWidth + 8 + $i, $y + $priceHeight, $black, $fontPath, $priceNumber);
        }

        imagedestroy($barcodeImg);

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
