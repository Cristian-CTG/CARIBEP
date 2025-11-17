<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Libro Mayor y Balance</title>

    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table-bordered th, .table-bordered td { padding: 6px; border: 1px solid #bbb; }
        .table-bordered th { background-color: #f4f4f4; }
        th, td { padding: 6px;}
        th { background-color: #f4f4f4; }
        .text-right { text-align: right; }
        .company-info { margin-bottom: 10px; }
        .company-title { font-size: 18px; font-weight: bold; margin-bottom: 5px; }
        .logo-box img { max-width: 120px; margin-bottom: 10px; }

        .section-title {
            font-size: 15px;
            font-weight: bold;
            margin-top: 25px;
            margin-bottom: 5px;
            border-bottom: 1px solid #777;
            padding-bottom: 3px;
        }

        .account-header {
            font-size: 14px;
            font-weight: bold;
            margin-top: 15px;
        }

        .summary-box {
            margin-bottom: 8px;
            font-size: 12px;
        }

        .subtle {
            color: #555;
        }

    </style>
</head>

<body>

@if(isset($company))
@php
    $logo     = $company->logo ?? null;
    $logoPath = $logo ? public_path("storage/uploads/logos/{$logo}") : null;
@endphp

<table width="100%" style="margin-bottom: 15px;">
    <tr>
        <!-- INFORMACIÓN DE LA EMPRESA A LA IZQUIERDA -->
        <td width="70%" valign="top" align="left" style="font-size: 12px;">
            <div style="font-size: 18px; font-weight: bold; margin-bottom: 3px;">
                {{ $company->name ?? '' }}
            </div>

            <div style="margin-bottom: 8px;"><strong>NIT:</strong> {{ $company->identification_number ?? $company->number ?? '' }}{{ $company->dv ? '-'.$company->dv : '' }}</div>
            <div style="margin-bottom: 8px;"><strong>Dirección:</strong> {{ $company->address ?? '' }}</div>
            <div style="margin-bottom: 8px;"><strong>Teléfono:</strong> {{ $company->phone ?? $company->telephone ?? '' }}</div>
            <div style="margin-bottom: 8px;"><strong>Email:</strong> {{ $company->email ?? '' }}</div>

            @if(method_exists($company, 'type_regime') && $company->type_regime)
                <div style="margin-bottom: 8px;"><strong>Régimen:</strong> {{ optional($company->type_regime)->name ?? '' }}</div>
            @endif
        </td>

        <!-- LOGO A LA DERECHA -->
        <td width="30%" valign="top" align="right">
            @if($logo && file_exists($logoPath))
                <img
                    src="data:{{ mime_content_type($logoPath) }};base64,{{ base64_encode(file_get_contents($logoPath)) }}"
                    alt="Logo"
                    style="max-height: 110px; max-width: 180px;"
                >
            @endif
        </td>
    </tr>
</table>
@endif

<h1>Libro Mayor y Balance</h1>

@if(isset($request))
    <p class="subtle">
        <strong>Fechas:</strong> {{ $request->date_start }} al {{ $request->date_end }}

        @if($request->filterAccount)
            | <strong>Cuenta:</strong> {{ $request->filterAccount }}
        @endif
        @if($request->filterLevel)
            | <strong>Nivel:</strong> {{ $request->filterLevel }}
        @endif
        @if($request->filterType)
            | <strong>Tipo:</strong> {{ $request->filterType }}
        @endif
        {{-- @if($request->hideEmpty)
            | <strong>Ocultando cuentas sin movimientos</strong>
        @endif --}}
    </p>
@endif

@foreach($records as $acc)

    <div class="section-title">
        {{ $acc->code }} - {{ $acc->name }}
    </div>

    <div class="summary-box">
        <strong>Saldo Inicial:</strong> {{ number_format($acc->saldo_inicial, 2) }} &nbsp;&nbsp;
        <strong>Saldo Final:</strong> {{ number_format($acc->saldo_final, 2) }}
    </div>

    @if(count($acc->movements) === 0)
        <p class="subtle">Sin movimientos en el periodo.</p>
        @continue
    @endif

    <table class="table-bordered">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Documento</th>
                <th>Detalle</th>
                <th class="text-right">Débito</th>
                <th class="text-right">Crédito</th>
                <th class="text-right">Saldo</th>
            </tr>
        </thead>

        <tbody>
            @foreach($acc->movements as $row)
                <tr>
                    <td>{{ $row->date }}</td>
                    <td>{{ $row->document }}</td>
                    <td>{{ $row->detail }}</td>
                    <td class="text-right">{{ number_format($row->debit, 2) }}</td>
                    <td class="text-right">{{ number_format($row->credit, 2) }}</td>
                    <td class="text-right">{{ number_format($row->balance, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

@endforeach


</body>
</html>
