<table>
    <thead>
        <tr>
            <th>Prefijo</th>
            <th>Número</th>
            <th>Fecha</th>
            <th>Descripción</th>
            <th>Código Cuenta</th>
            <th>Débito</th>
            <th>Crédito</th>
            <th>Documento Tercero</th>
            <th>Nombre Tercero</th>
            <th>Tipo Tercero</th>
            <th>Método Pago</th>
            <th>Nombre Banco</th>
        </tr>
    </thead>
    <tbody>
        @foreach($entries as $entry)
            @foreach($entry->details as $detail)
                <tr>
                    <td>{{ $entry->journal_prefix->prefix ?? '' }}</td>
                    <td>{{ $entry->number }}</td>
                    <td>{{ $entry->date }}</td>
                    <td>{{ $entry->description }}</td>
                    <td>{{ $detail->chartOfAccount->code ?? '' }}</td>
                    <td>{{ number_format($detail->debit, 2, '.', '') }}</td>
                    <td>{{ number_format($detail->credit, 2, '.', '') }}</td>
                    <td>{{ $detail->thirdParty->document ?? '' }}</td>
                    <td>{{ $detail->thirdParty->name ?? '' }}</td>
                    <td>{{ $detail->thirdParty ? $detail->thirdParty->getTypeName() : '' }}</td>
                    <td>{{ $detail->payment_method_name ?? '' }}</td>
                    <td>{{ $detail->bankAccount->description ?? '' }}</td>
                </tr>
            @endforeach
        @endforeach
    </tbody>
</table>
