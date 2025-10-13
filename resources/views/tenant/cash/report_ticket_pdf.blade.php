@php
    // Asegura que $is_resumido esté definido
    if (!isset($is_resumido)) {
        $is_resumido = false;
    }
    // Asegura que $filtered_documents esté definido
    if (!isset($filtered_documents)) {
        $filtered_documents = $cash->cash_documents;
    }
    // ...mismos cálculos y lógica que en report_pdf.blade.php...
    $establishment = $cash->user->establishment;
    $final_balance = 0;
    $cash_income = 0;
    $cash_documents = $filtered_documents->filter(function($doc) {
        return $doc->document_pos && $doc->document_pos->state_type_id !== '11';
    });
    $cashEgress = $cash->cash_documents->sum(function ($cashDocument) {
        return $cashDocument->expense_payment ? $cashDocument->expense_payment->payment : 0;
    });
    $cash_final_balance = 0;
    $document_count = 0;
    $cash_taxes = 0;
    $is_complete = !$is_resumido;
    $first_document = '';
    $last_document = '';
    $list = $cash_documents->filter(function ($item) {
        return $item->document_pos_id !== null;
    });
    if ($list->count() > 0) {
        $first_document = $list->first()->document_pos->series . '-' . $list->first()->document_pos->number;
        $last_document = $list->last()->document_pos->series . '-' . $list->last()->document_pos->number;
    }
    foreach ($cash_documents as $cash_document) {
        if ($cash_document->document_pos) {
            $cash_income += $cash_document->document_pos->getTotalCash();
            $final_balance += $cash_document->document_pos->getTotalCash();
            $cash_taxes += $cash_document->document_pos->total_tax;
            $document_count = $cash_document->document_pos->count();
            if (count($cash_document->document_pos->payments) > 0) {
                $pays = $cash_document->document_pos->payments;
                foreach ($methods_payment as $record) {
                    $record->sum = $record->sum + $pays->where('payment_method_type_id', $record->id)->sum('payment');
                }
                foreach ($cash_document->document_pos->payments as $payment) {
                    $paymentMethod = $methods_payment->firstWhere('id', $payment->payment_method_type_id);
                    if ($paymentMethod) {
                        $paymentMethod->transaction_count++;
                    }
                }
            }
        }
    }
    $cash_final_balance = $final_balance + $cash->beginning_balance - $cashEgress;
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte POS Ticket</title>
    <style>
        html {
            font-family: sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-spacing: 0;
            border: 1px solid black;
            /* Quitar margen negativo para evitar corte */
            margin-left: 0;
            /* Evitar saltos de página dentro de la tabla */
            page-break-inside: avoid;
        }

        tr, td, th {
            /* Evitar saltos de página dentro de filas y celdas */
            page-break-inside: avoid;
        }

        .section-title, .title, .totales {
            /* Evitar saltos de página en títulos y totales */
            page-break-inside: avoid;
        }

        .celda {
            text-align: center;
            padding: 5px;
            border: 0.1px solid black;
        }

        th {
            padding: 5px;
            text-align: center;
            border-color: #0088cc;
            border: 0.1px solid black;
        }

        .title {
            font-weight: bold;
            padding: 5px;
            font-size: 20px !important;
            text-decoration: underline;
        }

        p>strong {
            margin-left: 5px;
            font-size: 12px;
        }

        thead {
            font-weight: bold;
            background: #0088cc;
            color: white;
            text-align: center;
        }

        tbody {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .td-custom {
            line-height: 0.1em;
        }

        .totales {
            font-weight: bold;
            background: #0088cc;
            color: white;
            text-align: right;
        }

        html {
            font-family: sans-serif;
            font-size: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid black;
        }

        th,
        td {
            padding: 2px;
            border: 1px solid black;
            text-align: center;
            font-size: 8px;
        }

        th {
            background-color: #0088cc;
            color: white;
            font-weight: bold;
        }

        .title {
            font-weight: bold;
            text-align: center;
            font-size: 16px;
            text-decoration: underline;
        }

        p,
        p>strong {
            font-size: 8px;
        }

        .totales {
            font-weight: bold;
            background: #0088cc;
            color: white;
            text-align: right;
        }

        /* Estilos encabezado */
        html {
            font-family: sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid black;
            margin-left: -20%;
        }

        th,
        .celda {
            padding: 5px;
            border: 1px solid black;
            text-align: center;
        }

        th {
            background-color: #0088cc;
            color: white;
            font-weight: bold;
        }

        .title {
            font-weight: bold;
            text-align: center;
            font-size: 20px;
            text-decoration: underline;
        }
        
    </style>
</head>
<body>
    <div class="title">COMPROBANTE INFORME DIARIO</div>
    <div class="center">
        <div>Empresa: {{ $company->name }}</div>
        <div>N° Documento: {{ $company->number }}</div>
        <div>Establecimiento: {{ $establishment->description }}</div>
        <div>Fecha reporte: {{ date('Y-m-d') }}</div>
        <div>Vendedor: {{ $cash->user->name }}</div>
        <div>Fecha y hora apertura: {{ $cash->date_opening }} {{ $cash->time_opening }}</div>
        <div>Estado de caja: {{ $cash->state ? 'Aperturada' : 'Cerrada' }}</div>
        @if (!$cash->state)
            <div>Fecha y hora cierre: {{ $cash->date_closed }} {{ $cash->time_closed }}</div>
        @endif
    </div>
    <hr>
    <div>
        <div class="section-title">Comprobantes</div>
        <div>Tipo: Factura POS</div>
        <div>Inicial: {{ $first_document ?: 'No hay documentos' }}</div>
        <div>Final: {{ $last_document ?: 'No hay documentos' }}</div>
    </div>
    <hr>
    <table>
        <tr>
            <th>Saldo inicial</th>
            <th>Ingreso</th>
            <th>Egreso</th>
            <th>Saldo final</th>
        </tr>
        <tr>
            <td class="right">${{ number_format($cash->beginning_balance, 2, '.', ',') }}</td>
            <td class="right">${{ number_format($cash_income, 2, '.', ',') }}</td>
            <td class="right">${{ number_format($cashEgress, 2, '.', ',') }}</td>
            <td class="right">${{ number_format($cash->beginning_balance + $cash_income - $cashEgress, 2, '.', ',') }}</td>
        </tr>
    </table>
    <hr>
    @if ($cash_documents->count())
        <div class="section-title">Totales por medio de pago</div>
        <table>
            <tr>
                <th>#</th>
                <th>Medio</th>
                <th>Trans.</th>
                <th>Valor</th>
            </tr>
            @php $totalSum = 0; @endphp
            @foreach ($methods_payment as $item)
                @php $totalSum += $item->sum; @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->name }}</td>
                    <td class="center">{{ $item->transaction_count }}</td>
                    <td class="right">${{ number_format($item->sum, 2, '.', ',') }}</td>
                </tr>
            @endforeach
            <tr class="totales">
                <td colspan="3">Total:</td>
                <td class="right">${{ number_format($totalSum, 2, '.', ',') }}</td>
            </tr>
        </table>
    @else
        <div>No se encontraron registros de documentos.</div>
    @endif

    @if ($is_complete)
        <hr>
        @php
            // ...cálculos de totales por categoría (igual que en PDF)...
            $all_documents = [];
            $totalsByCategory = [];
            foreach ($cash_documents as $cash_document) {
                if ($cash_document->document_pos) {
                    $all_documents[] = $cash_document;
                }
            }
            foreach ($all_documents as $document) {
                foreach ($document->document_pos->items as $item) {
                    if ($item->refund == 0) {
                        $categoryId = $item->item->category_id ?? 'Categoría no especificada';
                        if (!isset($totalsByCategory[$categoryId])) {
                            $totalsByCategory[$categoryId] = [
                                'subtotal' => 0,
                                'discount' => 0,
                                'otherTaxes' => 0,
                                'iva' => [],
                                'total' => 0,
                            ];
                        }
                        $itemIvaRate = $item->tax->name ?? "";
                        $itemIvaValue = $item->total_tax ?? 0;
                        if (!isset($totalsByCategory[$categoryId]['iva'][$itemIvaRate])) {
                            $totalsByCategory[$categoryId]['iva'][$itemIvaRate] = 0;
                        }
                        $totalsByCategory[$categoryId]['iva'][$itemIvaRate] += $itemIvaValue;
                        $totalsByCategory[$categoryId]['subtotal'] += $item->subtotal ?? 0;
                        $totalsByCategory[$categoryId]['discount'] += $item->discount ?? 0;
                        $totalsByCategory[$categoryId]['otherTaxes'] += $item->other_taxes ?? 0;
                        $totalsByCategory[$categoryId]['total'] += $item->total ?? 0;
                    }
                }
            }
            foreach ($totalsByCategory as $categoryId => &$categoryTotals) {
                $categoryTotals['totalIva'] = array_sum($categoryTotals['iva']);
            }
            unset($categoryTotals);
        @endphp
        <div class="section-title">Totales por Categorías</div>
        <table>
            <tr>
                <th>Cat/IVA</th>
                <th>Tarifa</th>
                <th>Base</th>
                <th>Desc.</th>
                <th>IVA</th>
                <th>Neto</th>
            </tr>
            @php
                $grandTotalSubtotal = 0;
                $grandTotalDiscount = 0;
                $grandTotalIva = 0;
                $grandTotal = 0;
            @endphp
            @foreach ($totalsByCategory as $categoryId => $totals)
                @php
                    $grandTotalSubtotal += $totals['subtotal'] - $totals['totalIva'];
                    $grandTotalDiscount += $totals['discount'];
                    $grandTotalIva += $totals['totalIva'];
                    $grandTotal += $totals['total'];
                    $categoryName = $categories[$categoryId] ?? 'Categoría no especificada';
                @endphp
                <tr>
                    <td>{{ $categoryName }}</td>
                    <td>
                        @foreach ($totals['iva'] as $ivaName => $ivaValue)
                            @if ($ivaValue)
                                {{ $ivaName }}<br>
                            @endif
                        @endforeach
                    </td>
                    <td class="right">{{ number_format($totals['subtotal'] - $totals['totalIva'], 2) }}</td>
                    <td class="right">{{ number_format($totals['discount'], 2) }}</td>
                    <td class="right">{{ number_format($totals['totalIva'], 2) }}</td>
                    <td class="right">{{ number_format($totals['total'], 2) }}</td>
                </tr>
            @endforeach
            <tr class="totales">
                <td>Totales:</td>
                <td></td>
                <td class="right">{{ number_format($grandTotalSubtotal, 2) }}</td>
                <td class="right">{{ number_format($grandTotalDiscount, 2) }}</td>
                <td class="right">{{ number_format($grandTotalIva, 2) }}</td>
                <td class="right">{{ number_format($grandTotal, 2) }}</td>
            </tr>
        </table>
        <hr>
        <div class="section-title">Relación de Gastos</div>
        @php
            $expenses = $cash->cash_documents()
                ->whereNotNull('expense_payment_id')
                ->with(['expense_payment'])
                ->get();
            $expensePayments = collect();
            foreach($expenses as $expense) {
                if($expense->expense_payment) {
                    $expensePayments->push($expense->expense_payment);
                }
            }
        @endphp
        @if ($expensePayments->isNotEmpty())
            <table>
                <tr>
                    <th>#</th>
                    <th>Referencia</th>
                    <th>Total</th>
                </tr>
                @foreach ($expensePayments as $index => $expensePayment)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $expensePayment->reference ?? 'Sin referencia' }}</td>
                        <td class="right">{{ number_format($expensePayment->payment, 2) }}</td>
                    </tr>
                @endforeach
            </table>
        @else
            <div>No se encontraron gastos.</div>
        @endif
        <hr>
        @php
            // ...devoluciones en ventas (igual que en PDF)...
            $all_documents = [];
            $totalsByCategory = [];
            foreach ($cash_documents as $cash_document) {
                if ($cash_document->document_pos) {
                    $all_documents[] = $cash_document;
                }
            }
            foreach ($all_documents as $document) {
                foreach ($document->document_pos->items as $item) {
                    if ($item->refund == 1) {
                        $categoryId = $item->item->category_id ?? 'Categoría no especificada';
                        if (!isset($totalsByCategory[$categoryId])) {
                            $totalsByCategory[$categoryId] = [
                                'subtotal' => 0,
                                'discount' => 0,
                                'otherTaxes' => 0,
                                'iva' => [],
                                'total' => 0,
                            ];
                        }
                        $itemIvaRate = $item->tax->name ?? "";
                        $itemIvaValue = $item->total_tax ?? 0;
                        if (!isset($totalsByCategory[$categoryId]['iva'][$itemIvaRate])) {
                            $totalsByCategory[$categoryId]['iva'][$itemIvaRate] = 0;
                        }
                        $totalsByCategory[$categoryId]['iva'][$itemIvaRate] += $itemIvaValue;
                        $totalsByCategory[$categoryId]['subtotal'] += $item->subtotal ?? 0;
                        $totalsByCategory[$categoryId]['discount'] += $item->discount ?? 0;
                        $totalsByCategory[$categoryId]['otherTaxes'] += $item->other_taxes ?? 0;
                        $totalsByCategory[$categoryId]['total'] += $item->total ?? 0;
                    }
                }
            }
            foreach ($totalsByCategory as $categoryId => &$categoryTotals) {
                $categoryTotals['totalIva'] = array_sum($categoryTotals['iva']);
            }
            unset($categoryTotals);
        @endphp
        <div class="section-title">Totales por Categorías devoluciones en ventas</div>
        <table>
            <tr>
                <th>Cat/IVA</th>
                <th>Tarifa</th>
                <th>Base</th>
                <th>Desc.</th>
                <th>IVA</th>
                <th>Neto</th>
            </tr>
            @php
                $grandTotalSubtotal = 0;
                $grandTotalDiscount = 0;
                $grandTotalIva = 0;
                $grandTotal = 0;
            @endphp
            @foreach ($totalsByCategory as $categoryId => $totals)
                @php
                    $grandTotalSubtotal += $totals['subtotal'] - $totals['totalIva'];
                    $grandTotalDiscount += $totals['discount'];
                    $grandTotalIva += $totals['totalIva'];
                    $grandTotal += $totals['total'];
                    $categoryName = $categories[$categoryId] ?? 'Categoría no especificada';
                @endphp
                <tr>
                    <td>{{ $categoryName }}</td>
                    <td>
                        @foreach ($totals['iva'] as $ivaName => $ivaValue)
                            @if ($ivaValue)
                                {{ $ivaName }}<br>
                            @endif
                        @endforeach
                    </td>
                    <td class="right">{{ number_format($totals['subtotal'] - $totals['totalIva'], 2) }}</td>
                    <td class="right">{{ number_format($totals['discount'], 2) }}</td>
                    <td class="right">{{ number_format($totals['totalIva'], 2) }}</td>
                    <td class="right">{{ number_format($totals['total'], 2) }}</td>
                </tr>
            @endforeach
            <tr class="totales">
                <td>Totales:</td>
                <td></td>
                <td class="right">{{ number_format($grandTotalSubtotal, 2) }}</td>
                <td class="right">{{ number_format($grandTotalDiscount, 2) }}</td>
                <td class="right">{{ number_format($grandTotalIva, 2) }}</td>
                <td class="right">{{ number_format($grandTotal, 2) }}</td>
            </tr>
        </table>
        <hr>
        @php
            // ...totales por tarifa de IVA (igual que en PDF)...
            $totalsByIvaRate = [];
            $totalValorAntesIva = 0;
            $totalValorIva = 0;
            $totalGeneral = 0;
            foreach ($cash_documents as $cash_document) {
                if ($cash_document->document_pos) {
                    foreach ($cash_document->document_pos->items as $item) {
                        $ivaName = $item->tax->name ?? "";
                        $ivaRate = $item->tax->rate ?? 0;
                        $subtotal = $item->subtotal ?? 0;
                        if (!isset($totalsByIvaRate[$ivaName])) {
                            $totalsByIvaRate[$ivaName] = [
                                'rate' => $ivaRate,
                                'base_gravable' => 0,
                                'valor_iva' => 0,
                                'total' => 0,
                            ];
                        }
                        $subtotalSinIva = $subtotal / (1 + $ivaRate / 100);
                        $ivaCalculado = $subtotalSinIva * ($ivaRate / 100);
                        if ($item->refund == 1) {
                            $totalsByIvaRate[$ivaName]['base_gravable'] -= $subtotalSinIva;
                            $totalsByIvaRate[$ivaName]['valor_iva'] -= $ivaCalculado;
                            $totalsByIvaRate[$ivaName]['total'] -= $subtotal;
                            $totalValorAntesIva -= $subtotalSinIva;
                            $totalValorIva -= $ivaCalculado;
                            $totalGeneral -= $subtotal;
                        } else {
                            $totalsByIvaRate[$ivaName]['base_gravable'] += $subtotalSinIva;
                            $totalsByIvaRate[$ivaName]['valor_iva'] += $ivaCalculado;
                            $totalsByIvaRate[$ivaName]['total'] += $subtotal;
                            $totalValorAntesIva += $subtotalSinIva;
                            $totalValorIva += $ivaCalculado;
                            $totalGeneral += $subtotal;
                        }
                    }
                }
            }
        @endphp
        <div class="section-title">Totales por tarifa de IVA</div>
        <table>
            <tr>
                <th>Tarifa</th>
                <th>Base</th>
                <th>IVA</th>
                <th>Total</th>
            </tr>
            @foreach ($totalsByIvaRate as $ivaName => $totals)
                <tr>
                    <td>{{ $ivaName }} ({{ $totals['rate'] }}%)</td>
                    <td class="right">{{ number_format($totals['base_gravable'], 2, '.', ',') }}</td>
                    <td class="right">{{ number_format($totals['valor_iva'], 2, '.', ',') }}</td>
                    <td class="right">{{ number_format($totals['total'], 2, '.', ',') }}</td>
                </tr>
            @endforeach
            <tr class="totales">
                <td>Total</td>
                <td class="right">{{ number_format($totalValorAntesIva, 2, '.', ',') }}</td>
                <td class="right">{{ number_format($totalValorIva, 2, '.', ',') }}</td>
                <td class="right">{{ number_format($totalGeneral, 2, '.', ',') }}</td>
            </tr>
        </table>
        <hr>
        <div class="section-title">Inventario de máquinas</div>
        <table>
            <tr>
                <th>Tipo caja</th>
                <th>Número</th>
                <th>Tipo</th>
            </tr>
            @forelse($resolutions_maquinas as $resolution)
                <tr>
                    <td>{{ $resolution->cash_type ?? 'N/A' }}</td>
                    <td>{{ $resolution->plate_number ?? 'N/A' }}</td>
                    <td>{{ $resolution->electronic ? 'Electrónica' : 'No Electrónica' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">No se encontraron máquinas.</td>
                </tr>
            @endforelse
        </table>
    @endif
</body>
</html>
