<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Etiqueta de Producto</title>
    <style>
        @php
            $base = max($width, $height);
            $fontScale = ($base / 40);
        @endphp
        html, body {
            height: 100%;
            width: 100%;
            margin: 0;
            padding: 0;
            background: #fff !important;
        }
        body {
            height: 100%;
            width: 100%;
            margin: 0;
            padding: 0;
        }
        .etiqueta {
            width: {{ $width }}mm;
            height: {{ $height }}mm;
            background: #fff;
            display: flex;
            align-items: stretch;
            justify-content: stretch;
        }
        .etiqueta-content {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: stretch;
            box-sizing: border-box;
            text-align: center;
            overflow: hidden; /* <-- evita desbordes */
        }
        .company, .details, .code, .price {
            box-sizing: border-box;
            margin-bottom: 0.2mm; /* Reduce el espacio entre elementos */
        }
        .company {
            font-size: {{ 0.10 * $height }}mm; /* Reduce el tamaño relativo */
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
            -webkit-line-clamp: 2; /* máximo 2 líneas */
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
            max-height: 100%; /* Reduce la altura máxima */
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
    </style>
</head>
<body>
    <div class="etiqueta">
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
                <img src="data:image/png;base64,{{ $barcodeBase64 }}" alt="barcode">
            </div>
            <div class="code">{{ $item->internal_id }}</div>
            @if($fields['price'])
                <div class="price">
                    {{ $item->currency_type ? $item->currency_type->symbol : '$ ' }}
                    {{ number_format($item->sale_unit_price, 2) }}
                </div>
            @endif
        </div>
    </div>
</body>
</html>