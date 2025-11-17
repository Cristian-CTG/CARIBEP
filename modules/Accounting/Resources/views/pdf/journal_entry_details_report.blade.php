<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Detalles Contables</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
        th, td { border: 1px solid #ddd; padding: 6px; }
        th { background: #f4f4f4; }
        .totals { font-weight: bold; background: #eaeaea; }
        .text-right { text-align: right; }
        .company-info { margin-bottom: 10px; }
        .company-title { font-size: 18px; font-weight: bold; }

        /* Ajuste DEFINITIVO de ancho */
        .col-cuenta { width: 18%; }
        .col-nombre { width: 32%; }
        .col-tercero { width: 25%; }
        .col-debito { width: 12%; }
        .col-credito { width: 12%; }
    </style>
</head>
<body>

@if(isset($company))
<div class="company-info">
    <div class="company-title">{{ $company->name }}</div>
    <div><strong>NIT:</strong> {{ $company->identification_number }}{{ $company->dv ? "-$company->dv" : "" }}</div>
    <div><strong>Dirección:</strong> {{ $company->address }}</div>
    <div><strong>Teléfono:</strong> {{ $company->phone }}</div>
    <div><strong>Email:</strong> {{ $company->email }}</div>
</div>
@endif

<h2>Reporte de Detalles Contables</h2>

@if(!empty($filters['month']))
    <p><strong>Mes:</strong> {{ $filters['month'] }}</p>
@endif

@foreach($entries as $entry)

<table>

    {{-- CABECERA DEL ASIENTO (100% ancho real) --}}
    <tr>
        <th colspan="5" style="background:#eaeaea; text-align:left;">
            <strong>Comprobante:</strong> {{ $entry['prefix'] }}-{{ $entry['number'] }} &nbsp;&nbsp;
            <strong>Fecha:</strong> {{ $entry['date'] }} &nbsp;&nbsp;
            <strong>Concepto:</strong> {{ $entry['description'] }}
        </th>
    </tr>

    <thead>
        <tr>
            <th class="col-cuenta">Cuenta</th>
            <th class="col-nombre">Nombre Cuenta</th>
            <th class="col-tercero">Tercero</th>
            <th class="text-right col-debito">Débito</th>
            <th class="text-right col-credito">Crédito</th>
        </tr>
    </thead>

    <tbody>
        @foreach($entry['details'] as $detail)
        <tr>
            <td>{{ $detail['account_code'] }}</td>
            <td>{{ $detail['account_name'] }}</td>
            <td>{{ $detail['third_party_name'] }}</td>
            <td class="text-right">{{ number_format($detail['debit'], 2) }}</td>
            <td class="text-right">{{ number_format($detail['credit'], 2) }}</td>
        </tr>
        @endforeach

        <tr class="totals">
            <td colspan="3" class="text-right">Total comprobante</td>
            <td class="text-right">{{ number_format($entry['total_debit'], 2) }}</td>
            <td class="text-right">{{ number_format($entry['total_credit'], 2) }}</td>
        </tr>
    </tbody>

</table>

@endforeach

{{-- TOTAL GENERAL --}}
<h3 style="text-align:right;">
    Total general: 
    <span>{{ number_format($total_debit, 2) }}</span> / 
    <span>{{ number_format($total_credit, 2) }}</span>
</h3>

</body>
</html>