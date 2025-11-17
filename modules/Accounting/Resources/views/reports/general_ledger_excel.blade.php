<table>
    <thead>

        {{-- Encabezado empresa --}}
        <tr>
            <th colspan="6" style="background:#004b8d; color:white; font-size:16px; font-weight:bold; text-align:center;">
                {{ strtoupper($company->name ?? 'EMPRESA') }}
            </th>
        </tr>

        <tr>
            <th colspan="6" style="background:#005fa3; color:white; text-align:center; font-size:11px;">
                NIT: {{ $company->identification_number }}{{ $company->dv ? '-'.$company->dv : '' }}
            </th>
        </tr>

        <tr>
            <th colspan="6" style="background:#0074c7; color:white; text-align:center; font-size:11px;">
                Dirección: {{ $company->address }} | Teléfono: {{ $company->phone }} | Email: {{ $company->email }}
            </th>
        </tr>

        <tr>
            <th colspan="6" style="background:#0094d9; color:white; text-align:center; font-size:12px;">
                Libro Mayor y Balance del {{ $dateStart }} al {{ $dateEnd }}
            </th>
        </tr>

        {{-- Encabezado de columnas --}}
        <tr>
            <th style="background:#dbeafe; font-weight:bold;">Fecha</th>
            <th style="background:#dbeafe; font-weight:bold;">Código</th>
            <th style="background:#dbeafe; font-weight:bold;">Cuenta</th>
            <th style="background:#dbeafe; font-weight:bold; text-align:right;">Débito</th>
            <th style="background:#dbeafe; font-weight:bold; text-align:right;">Crédito</th>
            <th style="background:#dbeafe; font-weight:bold; text-align:right;">Saldo</th>
        </tr>

    </thead>

    <tbody>

        @foreach($records as $acc)

            {{-- Fila título de cuenta --}}
            <tr>
                <td colspan="6" style="background:#f0f9ff; font-weight:bold;">
                    {{ $acc->code }} - {{ $acc->name }}
                </td>
            </tr>

            {{-- Fila de saldos --}}
            <tr>
                <td></td>
                <td></td>
                <td style="font-weight:bold;">Saldo Inicial</td>
                <td></td>
                <td></td>
                <td style="text-align:right; font-weight:bold;">
                    {{ number_format($acc->saldo_inicial, 2, '.', ',') }}
                </td>
            </tr>

            {{-- Movimientos --}}
            @foreach($acc->movements as $m)
                <tr>
                    <td>{{ $m->date }}</td>
                    <td>{{ $acc->code }}</td>
                    <td>{{ $acc->name }}</td>

                    <td style="text-align:right;">
                        {{ number_format($m->debit, 2, '.', ',') }}
                    </td>

                    <td style="text-align:right;">
                        {{ number_format($m->credit, 2, '.', ',') }}
                    </td>

                    <td style="text-align:right;">
                        {{ number_format($m->balance, 2, '.', ',') }}
                    </td>
                </tr>
            @endforeach

            <tr>
                <td></td>
                <td></td>
                <td style="font-weight:bold;">Saldo Final</td>
                <td></td>
                <td></td>
                <td style="text-align:right; font-weight:bold;">
                    {{ number_format($acc->saldo_final, 2, '.', ',') }}
                </td>
            </tr>

        @endforeach

    </tbody>

</table>
