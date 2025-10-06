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
        table.grid {
            border-collapse: separate;
            border-spacing: {{ $gapX }}mm {{ $gapX }}mm; /* mismo espacio horizontal y vertical */
            width: 100%;
            height: 100%;
            table-layout: fixed;
            border: 1px solid transparent;
        }
        td.label-cell {
            width: {{ $width }}mm;
            height: {{ $height }}mm;
            padding: 0;
            margin: 0;
            vertical-align: top;
            text-align: center;
            overflow: hidden;
        }
        .etiqueta-content {
            width: 100%;
            height: 100%;
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
        $total = $repeat;
        $col = $columns;
        $rows = ceil($total / $col);
        $printed = 0;
    @endphp
    @for($i = 0; $i < $rows; $i++)
        <table class="grid">
            <tr>
                @php
                    $etiquetasRestantes = $total - $printed;
                    $etiquetasEnFila = min($col, $etiquetasRestantes);
                    $vaciasIzq = 0;
                    $vaciasDer = 0;
                    if($i == $rows - 1 && $etiquetasEnFila < $col) {
                        $vaciasIzq = floor(($col - $etiquetasEnFila) / 2);
                        $vaciasDer = $col - $etiquetasEnFila - $vaciasIzq;
                    }
                @endphp

                {{-- Celdas vacías a la izquierda --}}
                @for($k = 0; $k < $vaciasIzq; $k++)
                    <td class="label-cell"></td>
                @endfor

                {{-- Etiquetas --}}
                @for($j = 0; $j < $etiquetasEnFila; $j++)
                    <td class="label-cell">
                        @if($printed < $total)
                            <div class="etiqueta-content">
                                <div class="company">{{ strtoupper($companyName) }}</div>
                                @php
                                    $details = [];
                                    if($fields['name']) $details[] = $item->name;
                                    if($fields['brand'] && $item->brand) $details[] = $item->brand->name;
                                    if($fields['category'] && $item->category) $details[] = $item->category->name;
                                    if($fields['color'] && $item->color) $details[] = $item->color->name;
                                    if($fields['size'] && $item->size) $details[] = $item->size->name;
                                    $detailsText = implode(' | ', $details);
                                    $len = mb_strlen($detailsText);
                                    $fontSize = $len > 50 ? 0.06 * $height : 0.08 * $height;
                                @endphp
                                <div class="details" style="font-size: {{ $fontSize }}mm;">
                                    {{ $detailsText }}
                                </div>
                                <div class="barcode">
                                    @php
                                        $colour = [0,0,0];
                                        $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
                                        echo '<img style="max-width:80%;max-height:80%;height:auto;display:block;margin:0 auto;" src="data:image/png;base64,' .
                                            base64_encode($generator->getBarcode($item->internal_id, $generator::TYPE_CODE_128, 1.2, 30, $colour)) .
                                            '">';
                                    @endphp
                                </div>
                                <div class="code">{{ $item->internal_id }}</div>
                                @if($fields['price'])
                                    <div class="price">
                                        {{ $item->currency_type ? $item->currency_type->symbol : '$ ' }}
                                        {{ number_format($item->sale_unit_price, 2) }}
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
        </table>
        @if($i < $rows - 1)
            <div class="page-break"></div>
        @endif
    @endfor
</body>
</html>