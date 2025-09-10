<table class="combined-table">
    <thead>
        <tr>
            <th colspan="8">Información Básica</th>
            <th colspan="{{ 5 + ($taxes->count() * 2) }}">Detalles Financieros</th>
            @if($retention_types->count())
                <th colspan="2">Retenciones</th>
            @endif
            <th>Total</th>
        </tr>
        <tr>
            <th>FECHA</th>
            <th>TIPO DOC</th>
            <th>PREFIJO</th>
            <th>IDENTIFICACIÓN</th>
            <th>NOMBRE</th>
            <th>CORREO</th>
            <th>Telefono</th>
            <th>DIRECCIÓN</th>
            <th>Total/Excento</th>
            <th>Descuento</th>
            @foreach($taxes as $tax)
                <th>Base {{ str_contains($tax->name, '19') ? '19%' : (str_contains($tax->name, '5') ? '5%' : $tax->name) }}</th>
            @endforeach
            <th>Impuestos</th>
            @foreach($taxes as $tax)
                <th>{{ $tax->name }}</th>
            @endforeach
            <th>IVA Total</th>
            <th>Base + Impuesto</th>
            @if($retention_types->count())
                <th>Tipo</th>
                <th>Total.R</th>
            @endif
            <th>Total pagar</th>
        </tr>
    </thead>
    <tbody>
        @php
            $total = 0;
            $net_total = 0;
            $total_exempt = 0;
            $total_discount = 0;
            $total_tax_base = 0;
            $total_tax_amount = 0;
            $total_retention_by_type = [];
            $tax_totals_by_type = [];
            $base_totals_by_type = [];
            foreach($taxes as $tax) {
                $tax_totals_by_type[$tax->id] = 0; 
                $base_totals_by_type[$tax->id] = 0;
            }
            foreach($retention_types as $ret) {
                $total_retention_by_type[$ret['id']] = 0;
            }
            $retention_totals = [];
            foreach($retention_types as $ret) {
                $retention_totals[$ret['id']] = 0;
            }
        @endphp

        @foreach($records as $value)
            @php
                $row = $value->getDataReportSalesBook();
                $customer = $value->person;
                // Nueva lógica para identificar notas de crédito y anulaciones
                $is_credit_note = stripos($row['type_document_name'], 'crédit') !== false || 
                    ($value instanceof \App\Models\Tenant\DocumentPos && isset($row['state_type_id']) && $row['state_type_id'] === '11');
                $is_void_pos = false; // ya se incluye en la lógica de $is_credit_note
                $multiplier = $is_credit_note ? -1 : 1;

                if (!$is_credit_note) {
                    $total += floatval(str_replace(',', '', $row['total'])) * $multiplier;
                    $net_total += floatval(str_replace(',', '', $row['net_total'])) * $multiplier;
                    $total_exempt += floatval(str_replace(',', '', $row['total_exempt'])) * $multiplier;
                    $total_discount += floatval(str_replace(',', '', ($row['total_discount'] ?? 0))) * $multiplier;
                } else {
                    $total += floatval(str_replace(',', '', $row['total'])) * $multiplier;
                    $net_total += floatval(str_replace(',', '', $row['net_total'])) * $multiplier;
                    $total_exempt += floatval(str_replace(',', '', $row['total_exempt'])) * $multiplier;
                    $total_discount += floatval(str_replace(',', '', ($row['total_discount'] ?? 0))) * $multiplier;
                }

                $tax_names = collect($value->items)
                    ->pluck('tax.name')
                    ->unique()
                    ->implode(', ');

                $tax_totals = [
                    'base' => 0,
                    'tax' => 0
                ];
                
                foreach($taxes as $tax) {
                    $item_values = $value->getItemValuesByTax($tax->id);
                    $base_totals_by_type[$tax->id] += floatval(str_replace(',', '', $item_values['taxable_amount'])) * $multiplier;
                    $tax_totals['tax'] += floatval(str_replace(',', '', $item_values['tax_amount'])) * $multiplier;
                }
                $total_tax_base += $tax_totals['base'];
                $total_tax_amount += $tax_totals['tax'];

                // Procesar retenciones por tipo
                $taxes_raw = json_decode($value->getRawTaxes(), true) ?? [];
                $retentions_by_type = [];
                foreach($retention_types as $ret) {
                    $retentions_by_type[$ret['id']] = 0;
                }
                $retention_names = [];
                $retention_sum = 0;
                foreach($taxes_raw as $tax) {
                    if(isset($tax['is_retention']) && $tax['is_retention']) {
                        $amount = 0;
                        if (isset($tax['retention']) && floatval($tax['retention']) > 0) {
                            $amount = floatval($tax['retention']);
                        } elseif (isset($tax['total'])) {
                            $amount = floatval($tax['total']);
                        }
                        if(isset($retentions_by_type[$tax['id']])) {
                            $retentions_by_type[$tax['id']] += $amount * $multiplier;
                            if (!$is_credit_note) {
                                $retention_totals[$tax['id']] += $amount * $multiplier;
                            }
                            if($amount * $multiplier != 0) {
                                $retention_names[] = $tax['name'];
                                $retention_sum += $amount * $multiplier;
                            }
                        }
                    }
                }
                $retention_names_str = implode(' / ', array_unique($retention_names));
            @endphp
            <tr class="{{ $is_credit_note ? 'credit-note' : '' }}">
                <td class="celda">{{ $row['date_of_issue'] }}</td>
                <td class="celda">{{ $row['type_document_name'] }}</td>
                <td class="celda">{{ $row['number_full'] }}</td>
                <td class="celda">{{ $customer ? $customer->number : ($row['customer_number'] ?? '') }}</td>
                <td class="celda">{{ $customer ? $customer->name : ($row['customer_name'] ?? '') }}</td>
                @php
                    $email = $customer ? $customer->email : ($row['customer_email'] ?? '');
                    $email_formatted = str_replace('@', '<br>@', e($email));
                @endphp
                <td class="celda correo-cell">{!! $email_formatted !!}</td>
                <td class="celda">{{ $customer ? $customer->telephone : ($row['customer_telephone'] ?? '') }}</td>
                <td class="celda">{{ $customer ? $customer->address : ($row['customer_address'] ?? '') }}</td>
                <td class="celda text-right-td">{{ number_format(floatval(str_replace(',', '', $row['total_exempt'])) * $multiplier, 2, '.', '') }}</td>
                <td class="celda text-right-td">{{ number_format(floatval(str_replace(',', '', ($row['total_discount'] ?? 0))) * $multiplier, 2, '.', '') }}</td>
                @foreach($taxes as $tax)
                    @php
                        $item_values = $value->getItemValuesByTax($tax->id);
                        $base_amount = floatval(str_replace(',', '', $item_values['taxable_amount'])) * $multiplier;
                    @endphp
                    <td class="celda text-right-td">{{ number_format($base_amount, 2, '.', '') }}</td>
                @endforeach
                <td class="celda">{{ $tax_names }}</td>
                @foreach($taxes as $tax)
                    @php
                        $item_values = $value->getItemValuesByTax($tax->id);
                        $tax_amount = floatval(str_replace(',', '', $item_values['tax_amount'])) * $multiplier;
                        $tax_totals_by_type[$tax->id] += $tax_amount;
                    @endphp
                    <td class="celda text-right-td">{{ number_format($tax_amount, 2, '.', '') }}</td>
                @endforeach
                <td class="celda text-right-td">{{ number_format($tax_totals['tax'], 2, '.', '') }}</td>
                <td class="celda text-right-td">{{ number_format(floatval(str_replace(',', '', $row['net_total'])) * $multiplier + $tax_totals['tax'], 2, '.', '') }}</td>
                @if($retention_types->count())
                    <td class="celda text-right-td retencion-cell">
                        {{ $retention_names_str }}
                    </td>
                    <td class="celda text-right-td">
                        {{ $retention_sum != 0 ? number_format($retention_sum, 2, '.', '') : '' }}
                    </td>
                @endif
                <td class="celda text-right-td">{{ number_format(floatval(str_replace(',', '', $row['total'])) * $multiplier, 2, '.', '') }}</td>
            </tr>
        @endforeach

        <tr>
            <th colspan="8" class="celda text-right-td">TOTALES</th>
            <th>{{ number_format($total_exempt, 2, '.', '') }}</th>
            <th>{{ number_format($total_discount, 2, '.', '') }}</th>
            @foreach($taxes as $tax)
                <th>{{ number_format($base_totals_by_type[$tax->id], 2, '.', '') }}</th>
            @endforeach
            <th></th>
            @foreach($taxes as $tax)
                <th>{{ number_format($tax_totals_by_type[$tax->id], 2, '.', '') }}</th>
            @endforeach
            <th>{{ number_format($total_tax_amount, 2, '.', '') }}</th>
            <th>{{ number_format($total_tax_base + $total_tax_amount, 2, '.', '') }}</th>
            @if($retention_types->count())
                <th class="celda text-right-td retencion-cell">
                    @php
                        $all_names = [];
                        foreach($retention_types as $ret) {
                            if($retention_totals[$ret['id']] != 0) $all_names[] = $ret['name'];
                        }
                    @endphp
                    {{ implode(' / ', $all_names) }}
                </th>
                <th class="celda text-right-td">
                    @php
                        $total_retention_sum = 0;
                        foreach($retention_types as $ret) {
                            $total_retention_sum += $retention_totals[$ret['id']];
                        }
                    @endphp
                    {{ $total_retention_sum != 0 ? number_format($total_retention_sum, 2, '.', '') : '' }}
                </th>
            @endif
            <th>{{ number_format($total, 2, '.', '') }}</th>
        </tr>
    </tbody>
</table>
