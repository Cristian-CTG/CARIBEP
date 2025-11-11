<table>
    <thead>
        {{-- Encabezado de empresa --}}
        <tr>
            <th colspan="6" style="background:#004b8d; color:#ffffff; font-size:16px; font-weight:bold; text-align:center;">
                {{ strtoupper($company->name ?? 'EMPRESA') }}
            </th>
        </tr>
        <tr>
            <th colspan="6" style="background:#005fa3; color:#ffffff; text-align:center; font-size:11px;">
                NIT: {{ $company->identification_number ?? '' }}{{ $company->dv ? '-'.$company->dv : '' }}
            </th>
        </tr>
        <tr>
            <th colspan="6" style="background:#0074c7; color:#ffffff; text-align:center; font-size:11px;">
                Dirección: {{ $company->address ?? '-' }} | Teléfono: {{ $company->phone ?? '-' }} | Email: {{ $company->email ?? '-' }}
            </th>
        </tr>
        <tr>
            <th colspan="6" style="background:#0094d9; color:#ffffff; text-align:center; font-size:12px;">
                Balance de Prueba del {{ $dateStart }} al {{ $dateEnd }}
            </th>
        </tr>

        {{-- Encabezado de columnas --}}
        <tr>
            <th style="background:#dbeafe; text-align:left; font-weight:bold;">Código Contable</th>
            <th style="background:#dbeafe; text-align:left; font-weight:bold;">Nombre de la cuenta</th>
            <th style="background:#dbeafe; text-align:right; font-weight:bold;">Saldo inicial</th>
            <th style="background:#dbeafe; text-align:right; font-weight:bold;">Débitos</th>
            <th style="background:#dbeafe; text-align:right; font-weight:bold;">Créditos</th>
            <th style="background:#dbeafe; text-align:right; font-weight:bold;">Saldo final</th>
        </tr>
    </thead>

    <tbody>
        @foreach($data as $row)
            <tr>
                <td>{{ $row['code'] }}</td>
                <td>{{ $row['name'] }}</td>
                <td style="text-align:right;">{{ $row['saldo_inicial'] }}</td>
                <td style="text-align:right;">{{ $row['debitos'] }}</td>
                <td style="text-align:right;">{{ $row['creditos'] }}</td>
                <td style="text-align:right;">{{ $row['saldo_final'] }}</td>
            </tr>
        @endforeach

        {{-- Totales --}}
        <tr>
            <td colspan="2" style="font-weight:bold; background:#e0e7ff;">Totales:</td>
            <td style="font-weight:bold; background:#e0e7ff;">{{ $totals['saldo_inicial'] }}</td>
            <td style="font-weight:bold; background:#e0e7ff;">{{ $totals['debitos'] }}</td>
            <td style="font-weight:bold; background:#e0e7ff;">{{ $totals['creditos'] }}</td>
            <td style="font-weight:bold; background:#e0e7ff;">{{ $totals['saldo_final'] }}</td>
        </tr>
    </tbody>
</table>
