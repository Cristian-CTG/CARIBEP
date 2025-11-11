@php
    $logo_path = public_path('storage/uploads/logos/' . ($company->logo ?? ''));
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Balance de Prueba</title>
    <style>
        @page {
            margin: 30px 25px 40px 25px;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #222;
        }
        /* === ENCABEZADO === */
        .header-table {
            width: 100%;
            margin-bottom: 15px;
            border: none;
        }
        .header-table td {
            vertical-align: top;
            border: none;
        }
        .company-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }
        .company-info {
            font-size: 12px;
            color: #555;
            line-height: 1.5;
        }
        .logo-box {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            height: 120px; /* Altura del contenedor */
            overflow: hidden;
        }
        
        .company-logo {
            max-width: 100%;
            max-height: 120px; /* tamaño máximo de alto */
            width: auto;
            height: auto;
            object-fit: contain; /* mantiene proporción sin recortar */
            display: block;
        }
        h3 {
            text-align: center;
            margin: 15px 0 10px 0;
            font-size: 15px;
            color: #222;
            text-transform: uppercase;
        }
        /* === TABLA PRINCIPAL === */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        th, td {
            border: 1px solid #666;
            padding: 5px 4px;
            font-size: 10.5px;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        th:first-child, td:first-child,
        th:nth-child(2), td:nth-child(2) {
            text-align: left;
        }
        td {
            vertical-align: middle;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .totals {
            font-weight: bold;
            background: #f4f4f4;
            border-top: 2px solid #333;
        }
        /* === PIE DE PÁGINA === */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 35px;
            text-align: center;
            font-size: 9px;
            color: #666;
        }
        .page-number:after {
            content: counter(page);
        }
    </style>
</head>
<body>

    {{-- ===== ENCABEZADO DE EMPRESA ===== --}}
    <table class="header-table">
        <tr>
            <td style="width: 10%; text-align: left;">
                @if($company->logo && file_exists($logo_path))
                    <div class="logo-box">
                        <img
                            src="data:{{ mime_content_type($logo_path) }};base64,{{ base64_encode(file_get_contents($logo_path)) }}"
                            alt="Logo"
                            class="company-logo"
                        >
                    </div>
                @endif
            </td>
            <td style="width: 70%; padding-left: 1rem;">
                <div class="company-title">{{ strtoupper($company->name ?? '') }}</div>
                <div class="company-info">
                    <div><strong>NIT:</strong> {{ $company->identification_number ?? '' }}{{ $company->dv ? '-'.$company->dv : '' }}</div>
                    <div><strong>Dirección:</strong> {{ $company->address ?? '-' }}</div>
                    <div><strong>Teléfono:</strong> {{ $company->phone ?? '-' }}</div>
                    <div><strong>Email:</strong> {{ $company->email ?? '-' }}</div>
                </div>
            </td>
            <td style="width: 20%; text-align: right;">
                <div style="font-size:13px; color:#111; font-weight:bold;">Balance de Prueba</div>
                <div style="font-size:11px;">Del {{ $dateStart }} al {{ $dateEnd }}</div>
            </td>
        </tr>
    </table>

    <h3>Balance de Prueba General</h3>

    {{-- ===== TABLA PRINCIPAL ===== --}}
    <table>
        <thead>
            <tr>
                <th style="width: 12%">Código</th>
                <th>Cuenta</th>
                <th class="text-right" style="width: 15%">Saldo inicial</th>
                <th class="text-right" style="width: 15%">Débitos</th>
                <th class="text-right" style="width: 15%">Créditos</th>
                <th class="text-right" style="width: 15%">Saldo final</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $row)
                <tr>
                    <td>{{ $row['code'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td class="text-right">{{ number_format($row['saldo_inicial'], 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($row['debitos'], 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($row['creditos'], 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($row['saldo_final'], 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">No se encontraron registros</td>
                </tr>
            @endforelse
            @if(!empty($data))
            <tr class="totals">
                <td colspan="2" class="text-right">Totales:</td>
                <td class="text-right">{{ number_format($totals['saldo_inicial'], 2, ',', '.') }}</td>
                <td class="text-right">{{ number_format($totals['debitos'], 2, ',', '.') }}</td>
                <td class="text-right">{{ number_format($totals['creditos'], 2, ',', '.') }}</td>
                <td class="text-right">{{ number_format($totals['saldo_final'], 2, ',', '.') }}</td>
            </tr>
            @endif
        </tbody>
    </table>

</body>
</html>
