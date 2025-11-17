<table>
    <tr>
        <th colspan="5" style="background:#004b8d; color:white; font-size:16px; font-weight:bold; text-align:center;">
            {{ strtoupper($company->name) }}
        </th>
    </tr>

    <tr>
        <th colspan="5" style="background:#005fa3; color:white; text-align:center;">
            NIT: {{ $company->identification_number }}{{ $company->dv ? "-$company->dv" : "" }}
        </th>
    </tr>

    <tr>
        <th colspan="5" style="background:#0074c7; color:white; text-align:center;">
            Dirección: {{ $company->address }} | Tel: {{ $company->phone }} | Email: {{ $company->email }}
        </th>
    </tr>

    <tr>
        <th colspan="5" style="background:#0094d9; color:white; text-align:center; font-size:12px;">
            LIBRO DIARIO — {{ $filters['month'] ?? ($filters['date_start'] . ' al ' . $filters['date_end']) }}
        </th>
    </tr>

    <tr>
        <th style="background:#dbeafe; font-weight:bold;">Cuenta</th>
        <th style="background:#dbeafe; font-weight:bold;">Nombre Cuenta</th>
        <th style="background:#dbeafe; font-weight:bold;">Tercero</th>
        <th style="background:#dbeafe; font-weight:bold; text-align:right;">Débito</th>
        <th style="background:#dbeafe; font-weight:bold; text-align:right;">Crédito</th>
    </tr>

    @foreach($entries as $entry)

        <tr>
            <td colspan="5" style="background:#f0f9ff; font-weight:bold;">
                Comprobante: {{ $entry['prefix'] }}-{{ $entry['number'] }}
                | Fecha: {{ $entry['date'] }}
                | Concepto: {{ $entry['description'] }}
            </td>
        </tr>

        @foreach($entry['details'] as $d)
            <tr>
                <td>{{ $d['account_code'] }}</td>
                <td>{{ $d['account_name'] }}</td>
                <td>{{ $d['third_party_name'] }}</td>
                <td style="text-align:right;">{{ number_format($d['debit'], 2) }}</td>
                <td style="text-align:right;">{{ number_format($d['credit'], 2) }}</td>
            </tr>
        @endforeach

        <tr>
            <td colspan="3" style="font-weight:bold; text-align:right;">TOTAL COMPROBANTE</td>
            <td style="text-align:right; font-weight:bold;">{{ number_format($entry['total_debit'], 2) }}</td>
            <td style="text-align:right; font-weight:bold;">{{ number_format($entry['total_credit'], 2) }}</td>
        </tr>

    @endforeach

    <tr>
        <td colspan="3" style="font-weight:bold; background:#e8f3ff; text-align:right;">TOTAL GENERAL</td>
        <td style="font-weight:bold; text-align:right;">{{ number_format($total_debit, 2) }}</td>
        <td style="font-weight:bold; text-align:right;">{{ number_format($total_credit, 2) }}</td>
    </tr>

</table>