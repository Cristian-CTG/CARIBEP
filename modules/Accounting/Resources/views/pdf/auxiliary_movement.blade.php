@php
    use App\CoreFacturalo\Helpers\Number\NumberLetter;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Movimientos auxiliares</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
        }
        .totals {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>Reporte de Movimientos auxiliares</h1>
    @if(isset($company))
        <p><strong>Empresa:</strong> {{ $company->name ?? '' }}</p>
        <p><strong>NIT:</strong> {{ $company->identification_number ?? '' }}</p>
        <p><strong>Dirección:</strong> {{ $company->address ?? '' }}</p>
        <p><strong>Email:</strong> {{ $company->email ?? '' }}</p>
        <p><strong>Teléfono:</strong> {{ $company->phone ?? '' }}</p>
    @endif
    @if ($dateStart || $dateEnd)
        <p>Rango de fechas: {{ $dateStart }} a {{ $dateEnd }}</p>
    @endif
    <table>
        <tr>
            <th>Código</th>
            <th>Cuenta</th>
            <th>Comprobante</th>
            <th>Fecha</th>
            <th>Número de documento</th>
            <th>Nombre del tercero</th>
            <th>Descripción</th>
            <th>Saldo inicial</th>
            <th>Débito</th>
            <th>Crédito</th>
            <th>Saldo final</th>
        </tr>
        <tbody>
            @php
                $total_debit = 0;
                $total_credit = 0;
            @endphp
            @foreach ($accounts as $group)
                @php
                    $total_debit += $group['total_debit'];
                    $total_credit += $group['total_credit'];
                @endphp
                <tr>
                    <td colspan="7">
                        <strong>Cuenta contable:</strong> {{ $group['account_code'] }} {{ $group['account_name'] }}
                    </td>
                    <td>{{ NumberLetter::numberFormat($group['balance_initial']) }}</td>
                    <td>{{ NumberLetter::numberFormat($group['total_debit']) }}</td>
                    <td>{{ NumberLetter::numberFormat($group['total_credit']) }}</td>
                    <td>{{ NumberLetter::numberFormat($group['balance_final']) }}</td>
                </tr>
                    @foreach ($group['details'] as $row)
                        <tr>
                            <td>{{ $row['account_code'] }}</td>
                            <td>{{ $row['account_name'] }}</td>
                            <td>{{ $row['document_info']['number'] ?? '' }}</td>
                            <td>{{ $row['date'] }}</td>
                            <td>{{ $row['third_party_number'] ?? '' }}</td>
                            <td>{{ $row['third_party_name'] ?? '' }}</td>
                            <td>{{ $row['description'] }}</td>
                            <td class="text-right">0</td>
                            <td class="text-right">{{ NumberLetter::numberFormat($row['debit']) }}</td>
                            <td class="text-right">{{ NumberLetter::numberFormat($row['credit']) }}</td>
                            <td class="text-right">0</td>
                        </tr>
                    @endforeach
            @endforeach
            <tr>
                <td>TOTAL</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td class="text-right"></td>
                <td class="text-right">{{ NumberLetter::numberFormat($total_debit) }}</td>
                <td class="text-right">{{ NumberLetter::numberFormat($total_credit) }}</td>
                <td class="text-right"></td>
            </tr>
        </tbody>
    </table>
    {{-- {{dd($accounts)}} --}}
</body>
</html>