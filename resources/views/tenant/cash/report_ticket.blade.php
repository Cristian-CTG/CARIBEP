@php
    $establishment = $cash->user->establishment;
    $final_balance = 0;
    $cash_income = 0;
    $cashEgress = $cash->cash_documents->sum(function ($cashDocument) {
        return $cashDocument->expense_payment ? $cashDocument->expense_payment->payment : 0;
    });
    $cash_final_balance = 0;
    $document_count = 0;
    $cash_taxes = 0;
    $cash_documents = $filtered_documents ?? $cash->cash_documents;
    $is_complete = $only_head === 'resumido' ? false : true;
    $first_document = '';
    $last_document = '';

    $list = $cash_documents->filter(function ($item) {
        return $item->document_pos_id !== null;
    });
    if ($list->count() > 0) {
        $first_document = $list->first()->document_pos->series . '-' . $list->first()->document_pos->number;
        $last_document = $list->last()->document_pos->series . '-' . $list->last()->document_pos->number;
    }

    // Inicializar methods_payment si no está definido
    if (!isset($methods_payment)) {
        $methods_payment = collect();
        
        // Procesar pagos de documentos
        foreach ($cash_documents as $cash_document) {
            if ($cash_document->document_pos && $cash_document->document_pos->payments) {
                foreach ($cash_document->document_pos->payments as $payment) {
                    $method_key = null;
                    $method_name = '';
                    
                    // Determinar qué campo usar para identificar el método de pago
                    if ($payment->payment_method_type_id) {
                        $method_key = 'type_' . $payment->payment_method_type_id;
                        $method_name = $payment->payment_method_type 
                            ? $payment->payment_method_type->description 
                            : 'Método no especificado';
                    } elseif ($payment->payment_method_id) {
                        $method_key = 'method_' . $payment->payment_method_id;
                        $method_name = $payment->payment_method 
                            ? $payment->payment_method->name 
                            : 'Método no especificado';
                    }
                    
                    if ($method_key) {
                        if (!$methods_payment->has($method_key)) {
                            $methods_payment->put($method_key, (object)[
                                'id' => $method_key,
                                'name' => $method_name,
                                'sum' => 0,
                                'transaction_count' => 0
                            ]);
                        }
                        
                        $method = $methods_payment->get($method_key);
                        $method->sum += $payment->payment;
                        $method->transaction_count++;
                    }
                }
            }
        }
        
        $methods_payment = $methods_payment->filter(function($method) {
            return $method->sum > 0;
        })->values();
    }

    // Calcular totales
    foreach ($cash_documents as $cash_document) {
        if ($cash_document->document_pos) {
            $cash_income += $cash_document->document_pos->getTotalCash();
            $final_balance += $cash_document->document_pos->getTotalCash();
            $cash_taxes += $cash_document->document_pos->total_tax;
            $document_count = $cash_document->document_pos->count();
        }
    }
    $cash_final_balance = $final_balance + $cash->beginning_balance - $cashEgress;
@endphp

<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="Content-Type" content="application/pdf; charset=utf-8" />
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>Reporte POS - {{ $cash->user->name }} - {{ $cash->date_opening }} {{ $cash->time_opening }}</title>
        <style>
            html {
                font-family: sans-serif;
                font-size: 12px;
            }

            table {
                width: 100%;
                border-spacing: 0;
                border: 1px solid black;
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
        <div>
            <div style="margin-top: -30px;" class="text-center">
                <p>
                    <strong>Empresa: </strong>{{ $company->name }} <br>
                    <strong>N° Documento: </strong>{{ $company->number }} <br>
                    <strong>Establecimiento: </strong>{{ $establishment->description }} <br>
                    <strong>Fecha reporte: </strong>{{ date('Y-m-d') }} <br>
                    <strong>Vendedor:</strong> {{ $cash->user->name }} <br>
                    <strong>Fecha y hora apertura:</strong> {{ $cash->date_opening }} {{ $cash->time_opening }} <br>
                    <strong>Estado de caja:</strong> {{ $cash->state ? 'Aperturada' : 'Cerrada' }}
                    @if (!$cash->state)
                        <br>
                        <strong>Fecha y hora cierre:</strong> {{ $cash->date_closed }} {{ $cash->time_closed }}
                    @endif
                </p>
            </div>
        </div>

        <table>
            <tr>
                <th>Egreso</th>
            </tr>
            <tr>
                <td>${{ number_format($cashEgress, 2) }}</td>
            </tr>
        </table>

        @if($cash_documents->count())
            <div>
                <h3>Totales por medio de pago</h3>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Medio de Pago</th>
                            @if($only_head !== 'resumido')
                                <th>Valor Sistema</th>
                            @else
                            <th>Valor Manual</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @php $totalSum = 0; @endphp
                        @php $totalTransactions = 0; @endphp
                        @foreach ($methods_payment as $item)
                            @php
                                $totalSum += $item->sum;
                                $totalTransactions += $item->transaction_count;
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->name }}</td>
                                @if($only_head !== 'resumido')
                                    <td>${{ number_format($item->sum, 2, '.', ',') }}</td>
                                @else
                                <td style="border-bottom: 1px dotted #000;"></td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p>No se encontraron registros de documentos.</p>
        @endif
    </body>
</html>