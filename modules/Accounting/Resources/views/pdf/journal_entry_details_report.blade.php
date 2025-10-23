<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Detalles Contables</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; }
        .totals { font-weight: bold; background-color: #eaeaea; }
        .text-right { text-align: right; }
        .company-info { margin-bottom: 10px; }
        .company-title { font-size: 18px; font-weight: bold; }
    </style>
</head>
<body>
    {{-- @php
        $company = \Modules\Factcolombia1\Models\Tenant\Company::active();
    @endphp
    <div class="company-info">
        <div class="company-title">{{ $company->name ?? '' }}</div>
        <div><strong>NIT:</strong> {{ $company->identification_number ?? $company->number ?? '' }}{{ $company->dv ? '-'.$company->dv : '' }}</div>
        <div><strong>Dirección:</strong> {{ $company->address ?? '' }}</div>
        <div><strong>Teléfono:</strong> {{ $company->phone ?? $company->telephone ?? '' }}</div>
        <div><strong>Email:</strong> {{ $company->email ?? '' }}</div>
        <div><strong>Régimen:</strong> {{ optional($company->type_regime)->name ?? '' }}</div>
    </div> --}}
    <h1>Reporte de Detalles Contables</h1>
    @if (!empty($filters['month']))
        <p>Mes: {{ $filters['month'] }}</p>
    @endif
    <table>
        <thead>
            <tr>
                <th>Comprobante</th>
                <th>Fecha de creación</th>
                <th>Concepto</th>
                <th>Tercero implicado</th>
                <th>N° Cuenta Contable</th>
                <th>Débito</th>
                <th>Crédito</th>
            </tr>
        </thead>
        <tbody>
            @foreach($groups as $group)
                @foreach($group['detalles'] as $row)
                    <tr>
                        <td>{{ $group['prefijo_numero'] }}</td>
                        <td>{{ $group['fecha'] }}</td>
                        <td>{{ $group['concepto'] }}</td>
                        <td>{{ $row->third_party_name }}</td>
                        <td>{{ $row->chart_of_account_id }}</td>
                        <td>{{ number_format($row->debit, 2) }}</td>
                        <td>{{ number_format($row->credit, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="totals">
                    <td colspan="4"></td>
                    <td class="text-right">Total para {{ $group['prefijo_numero'] }}</td>
                    <td>{{ number_format($group['total_debito'], 2) }}</td>
                    <td>{{ number_format($group['total_credito'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>