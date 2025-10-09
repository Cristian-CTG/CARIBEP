<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Etiquetas</title>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            background: #fff;
            width: 100%;
            height: 100%;
        }
        .labels-sheet {
            width: {{ $pageWidth }}mm;
        }
        table.grid {
            border-collapse: separate;
            border-spacing: {{ $gapX }}mm {{ $gapX }}mm;
            width: auto;
            table-layout: fixed;
            border: 0.1px solid white;
        }
        td.label-cell {
            width: {{ $width }}mm;
            max-width: {{ $width }}mm;
            height: {{ $height }}mm;
            max-height: {{ $height }}mm;
            padding: 0;
            margin: 0;
            vertical-align: top;
            text-align: center;
            overflow: hidden;
            box-sizing: border-box;
        }
        .etiqueta-content {
            width: 100%;
            max-width: 100%;
            height: 100%;
            max-height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: stretch;
            box-sizing: border-box;
            overflow: hidden;
            text-align: center;
        }
        .company, .details, .code, .price {
            box-sizing: border-box;
            margin-bottom: 0.2mm;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .company {
            font-size: {{ 0.10 * $height }}mm;
        }
        .details {
            font-size: {{ 0.08 * $height }}mm;
            max-height: {{ 0.18 * $height }}mm;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: pre-line;
            word-break: break-all;
            line-height: 1.1;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        .barcode {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            flex: 0 0 auto;
        }
        .barcode img {
            max-width: 80%;
            max-height: 100%;
            height: auto;
            display: block;
            margin: 0 auto;
            box-sizing: border-box;
        }
        .code {
            font-size: {{ 0.08 * $height }}mm;
        }
        .price {
            font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
            font-size: {{ 0.10 * $height }}mm;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    @php
        $isMultiple = isset($items);
        $total = $repeat;
        $col = $columns;
        $rows = ceil($total / $col);
        $printed = 0;
    @endphp
    <div class="labels-sheet">
        <table class="grid">
            @for($i = 0; $i < $rows; $i++)
                <tr>
                    @php
                        $etiquetasRestantes = $total - $printed;
                        $etiquetasEnFila = min($col, $etiquetasRestantes);
                        $vaciasDer = ($i == $rows - 1) ? $col - $etiquetasEnFila : 0;
                    @endphp

                    {{-- Etiquetas --}}
                    @for($j = 0; $j < $etiquetasEnFila; $j++)
                        <td class="label-cell">
                            @if($printed < $total)
                                <div class="etiqueta-content">
                                    <div class="company">{{ strtoupper($companyName) }}</div>
                                    @php
                                        // Soporte para uno o varios productos
                                        $currentItem = $isMultiple ? $items[$printed] : $item;
                                        $details = [];
                                        if($fields['name']) $details[] = $currentItem->name;
                                        if($fields['brand'] && $currentItem->brand) $details[] = $currentItem->brand->name;
                                        if($fields['category'] && $currentItem->category) $details[] = $currentItem->category->name;
                                        if($fields['color'] && $currentItem->color) $details[] = $currentItem->color->name;
                                        if($fields['size'] && $currentItem->size) $details[] = $currentItem->size->name;
                                        $detailsText = implode(' | ', $details);
                                        $len = mb_strlen($detailsText);
                                        $fontSize = $len > 50 ? 0.06 * $height : 0.08 * $height;
                                    @endphp
                                    <div class="details" style="font-size: {{ $fontSize }}mm;">
                                        {{ $detailsText }}
                                    </div>
                                    <div class="barcode">
                                        @php
                                            $barcodeWidth = min(3, round($width * 0.04));
                                            $barcodeHeight = max(40, round($height * 0.7));
                                            $colour = [0,0,0];
                                            $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
                                            echo '<img style="max-width:95%;max-height:95%;height:auto;display:block;margin:0 auto; padding: 5px;" src="data:image/png;base64,' .
                                                base64_encode($generator->getBarcode($currentItem->internal_id, $generator::TYPE_CODE_128, 1.2, 30, $colour)) .
                                                '">';
                                        @endphp
                                    </div>
                                    <div class="code">{{ $currentItem->internal_id }}</div>
                                    @if($fields['price'])
                                        <div class="price">
                                            {{ $currentItem->currency_type ? $currentItem->currency_type->symbol : '$ ' }}
                                            {{ number_format($currentItem->sale_unit_price, 2) }}
                                        </div>
                                    @endif
                                </div>
                                @php $printed++; @endphp
                            @endif
                        </td>
                    @endfor

                    {{-- Celdas vacías a la derecha --}}
                    @for($k = 0; $k < $vaciasDer; $k++)
                        <td class="label-cell"></td>
                    @endfor

                </tr>
            @endfor
        </table>
    </div>
</body>
</html>