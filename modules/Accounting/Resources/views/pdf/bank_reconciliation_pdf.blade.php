{{-- filepath: modules/Accounting/Resources/views/pdf/bank_reconciliation_pdf.blade.php --}}
@php
    $logo_path = public_path("storage/uploads/logos/{$company->logo}");
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Conciliación Bancaria #{{ $reconciliation->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
        .header-table { width: 100%; margin-bottom: 15px; }
        .header-table td { vertical-align: top; border: none; }
        .company-title { font-size: 18px; font-weight: bold; color: #222; }
        .company-info { font-size: 11px; color: #444; line-height: 1.6; }
        .logo-box img { max-width: 40px; }
        .section-title { color: red; font-size: 15px; font-weight: bold; margin-top: 20px; margin-bottom: 8px; }
        .info-group { margin-bottom: 10px; }
        .bordered {
            border: 1.5px solid #bbb;
            border-radius: 7px;
            padding: 10px 15px;
            margin-bottom: 15px;
            background: #fafbfc;
        }
        .info-label { font-weight: bold; display: inline-block; width: 140px; color: #333; }
        .info-value { display: inline-block; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #bbb; padding: 7px; font-size: 11px; }
        th { background-color: #f4f4f4; }
        .totals { font-weight: bold; background-color: #eaeaea; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .summary-table td { border: none; }
    </style>
</head>
<body>
    {{-- Encabezado empresa --}}
    <table class="header-table">
        <tr>
            <td style="width: 20%; text-align: left;">
                @if($company->logo && file_exists($logo_path))
                    <div class="logo-box">
                        <img src="data:{{mime_content_type($logo_path)}};base64,{{base64_encode(file_get_contents($logo_path))}}" alt="Logo" width="120">
                    </div>
                @endif
            </td>
            <td style="width: 55%; padding-left: 1rem;">
                <div class="company-title">{{ $company->name ?? '' }}</div>
                <div class="company-info">
                    <div><strong>NIT:</strong> {{ $company->identification_number ?? $company->number ?? '' }}{{ $company->dv ? '-'.$company->dv : '' }}</div>
                    <div><strong>Dirección:</strong> {{ $company->address ?? '' }}</div>
                    <div><strong>Teléfono:</strong> {{ $company->phone ?? $company->telephone ?? '' }}</div>
                    <div><strong>Email:</strong> {{ $company->email ?? '' }}</div>
                    <div><strong>Régimen:</strong> {{ optional($company->type_regime)->name ?? '' }}</div>
                </div>
            </td>
            <td style="width: 25%; text-align: right; vertical-align: top;">
                <div class="section-title">CONCILIACIÓN BANCARIA</div>
                    <span class="info-label">Banco:</span>
                    <span class="info-value">
                        {{ $reconciliation->bankAccount->bank->description ?? '' }}
                    </span>
                    <br>
                    <span class="info-label">N° Cuenta:</span>
                    <span class="info-value">
                        {{ $reconciliation->bankAccount->number ?? '' }}
                    </span>
                    <br>
                    <span class="info-label">Tipo de cuenta:</span>
                    <span class="info-value">
                        {{ $reconciliation->bankAccount->description ?? '' }}
                    </span>
                    <br>
                    <span class="info-label">Moneda:</span>
                    <span class="info-value">
                        {{ $reconciliation->bankAccount->currency->name ?? '' }}
                    </span>
                </div>
            </td>
        </tr>
    </table>

    {{-- Grupo: Cuenta bancaria y periodo --}}
    <div class="info-group bordered">
        <span class="info-label">Cuenta bancaria:</span>
        <span class="info-value">
            {{ $reconciliation->bankAccount->description ?? '' }}
            @if($reconciliation->bankAccount && $reconciliation->bankAccount->number)
                - {{ $reconciliation->bankAccount->number }}
            @endif
        </span>
        <span style="margin-left:40px"></span>
        <span class="info-label">Periodo (Mes):</span>
        <span class="info-value">{{ $reconciliation->month }}</span>
    </div>

    {{-- Grupo: Saldos --}}
    <div class="info-group bordered">
        <table class="summary-table" style="width:100%">
            <tr>
                <td class="info-label">Saldo en extracto:</td>
                <td class="info-value">${{ number_format($reconciliation->saldo_extracto, 2, ',', '.') }}</td>
                <td class="info-label">Saldo en libros:</td>
                <td class="info-value">${{ number_format($reconciliation->saldo_libro, 2, ',', '.') }}</td>
                <td class="info-label">Diferencia a conciliar:</td>
                <td class="info-value">${{ number_format($reconciliation->diferencia, 2, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    {{-- Entradas (Créditos) --}}
    <div class="section-title" style="color: blue">Entradas (Créditos)</div>
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Tercero</th>
                <th>Cheque</th>
                <th>Origen</th>
                <th>N° Soporte</th>
                <th>Cheque</th>
                <th>Concepto</th>
                <th class="text-right">Valor</th>
            </tr>
        </thead>
        <tbody>
            @php $total_mas = 0; @endphp
            @forelse($detalles_mas as $row)
                <tr>
                    <td>{{ $row->date }}</td>
                    <td>{{ $row->third_party_name }}</td>
                    <td>{{ $row->check }}</td>
                    <td>{{ $row->source }}</td>
                    <td>{{ $row->support_number }}</td>
                    <td>{{ $row->check }}</td>
                    <td>{{ $row->concept }}</td>
                    <td class="text-right">${{ number_format($row->value, 2, ',', '.') }}</td>
                </tr>
                @php $total_mas += $row->value; @endphp
            @empty
                <tr>
                    <td colspan="8" style="text-align:center; color:#888;">No hay registros pendientes para conciliar.</td>
                </tr>
            @endforelse
            <tr class="totals">
                <td colspan="7" class="text-right">Total Entradas</td>
                <td class="text-right">${{ number_format($total_mas, 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Salidas (Debitos) --}}
    <div class="section-title">Salidas (Debitos)</div>
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Tercero</th>
                <th>Cheque</th>
                <th>Origen</th>
                <th>N° Soporte</th>
                <th>Cheque</th>
                <th>Concepto</th>
                <th class="text-right">Valor</th>
            </tr>
        </thead>
        <tbody>
            @php $total_menos = 0; @endphp
            @forelse($detalles_menos as $row)
                <tr>
                    <td>{{ $row->date }}</td>
                    <td>{{ $row->third_party_name }}</td>
                    <td>{{ $row->check }}</td>
                    <td>{{ $row->source }}</td>
                    <td>{{ $row->support_number }}</td>
                    <td>{{ $row->check }}</td>
                    <td>{{ $row->concept }}</td>
                    <td class="text-right">${{ number_format($row->value, 2, ',', '.') }}</td>
                </tr>
                @php $total_menos += $row->value; @endphp
            @empty
                <tr>
                    <td colspan="8" style="text-align:center; color:#888;">No hay registros pendientes para conciliar.</td>
                </tr>
            @endforelse
            <tr class="totals">
                <td colspan="7" class="text-right">Total Salidas</td>
                <td class="text-right">${{ number_format($total_menos, 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Sumas totales --}}
    @php
    $diferencia_bd = round($reconciliation->diferencia, 2);
    $diferencia_tablas = round($total_mas - $total_menos, 2);
    $diferencia_final = $diferencia_bd + $diferencia_tablas; // Suma, no resta
    @endphp
    <div class="info-group">
        <table class="summary-table">
            <tr>
                <td class="info-label">Total Entradas:</td>
                <td class="info-value">${{ number_format($total_mas, 2, ',', '.') }}</td>
                <td class="info-label">Total Salidas:</td>
                <td class="info-value">${{ number_format($total_menos, 2, ',', '.') }}</td>
                <td class="info-label">Diferencia Conciliada:</td>
                <td class="info-value">${{ number_format($diferencia_tablas, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="info-label" colspan="4" style="text-align:right;">
                    Diferencia a conciliar + Diferencia conciliada:
                </td>
                <td class="info-value" colspan="2" style="font-weight:bold; color: {{ $diferencia_final == 0 ? 'green' : 'red' }}">
                    ${{ number_format($diferencia_final, 2, ',', '.') }}
                </td>
            </tr>
        </table>
    </div>
</body>
</html>