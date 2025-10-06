<div class="etiqueta" style="width:100%;height:100%;">
    <div class="etiqueta-content" style="width:100%;height:100%;">
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