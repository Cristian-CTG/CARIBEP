@php
    $number = $journalEntry->journal_prefix->prefix . '-' . $journalEntry->number;
    $details = $journalEntry->details;
    $statuses = [ 'rejected' => 'Rechazado', 'draft' => 'Borrador', 'posted' => 'Aprobado'];
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Asiento contable {{ $number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
            line-height: 1.4;
        }

        .header {
            text-align: center;
            /* border-bottom: 2px solid #333; */
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .header h2 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
            color: #000;
        }

        .info-section {
            margin-bottom: 25px;
            border: 1px solid #ccc;
            padding: 15px;
            background-color: #f9f9f9;
        }

        .info-row {
            margin-bottom: 8px;
        }

        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 100px;
            color: #333;
        }

        .info-value {
            display: inline-block;
            color: #555;
        }

        .status {
            padding: 4px 8px;
            border: 1px solid #666;
            background-color: #eee;
            font-size: 12px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            border: 1px solid #333;
        }

        th {
            background-color: #ddd;
            border: 1px solid #333;
            padding: 10px;
            text-align: left;
            font-weight: bold;
            color: #000;
        }

        td {
            border: 1px solid #666;
            padding: 8px;
            vertical-align: top;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .account-code {
            font-family: "Courier New", monospace;
            font-weight: bold;
            color: #000;
        }

        .total-row {
            background-color: #eee;
            font-weight: bold;
        }

        .total-row td {
            border-top: 2px solid #333;
            padding: 12px 8px;
        }

        .amount {
            font-family: "Courier New", monospace;
            font-weight: normal;
        }

        .total-amount {
            font-family: "Courier New", monospace;
            font-weight: bold;
            color: #000;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Asiento Contable: {{ $number }}</h2>
    </div>

    <div class="info-section">
        <div class="info-row">
            <span class="info-label">Fecha:</span>
            <span class="info-value">{{ $journalEntry->date }}</span>
        </div>

        <div class="info-row">
            <span class="info-label">Estado:</span>
            <span class="info-value">
                <span class="">{{ $statuses[$journalEntry->status] }}</span>
            </span>
        </div>

        <div class="info-row">
            <span class="info-label">Descripción:</span>
            <span class="info-value">{{ $journalEntry->description }}</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Código de cuenta</th>
                <th>Nombre de cuenta</th>
                <th class="text-right">Debe</th>
                <th class="text-right">Haber</th>
            </tr>
        </thead>
        <tbody>
            @foreach($details as $detail)
                <tr>
                    <td class="account-code">{{ $detail->chartOfAccount->code }}</td>
                    <td>{{ $detail->chartOfAccount->name }}</td>
                    <td class="text-right amount">{{ number_format($detail->debit, 2) }}</td>
                    <td class="text-right amount">{{ number_format($detail->credit, 2) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2" class="text-right">Total</td>
                <td class="text-right total-amount">{{ number_format($details->sum('debit'), 2) }}</td>
                <td class="text-right total-amount">{{ number_format($details->sum('credit'), 2) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>