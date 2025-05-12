<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Situación Financiera</title>
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
    <h1>Reporte de Situación Financiera</h1>
    <p>Rango de fechas: {{ $dateStart }} a {{ $dateEnd }}</p>

    <h2>Activos</h2>
    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Saldo</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($assets as $asset)
                <tr>
                    <td>{{ $asset['code'] }}</td>
                    <td>{{ $asset['name'] }}</td>
                    <td>{{ number_format($asset['saldo'], 2) }}</td>
                </tr>
            @endforeach
            <tr class="totals">
                <td colspan="2">Total Activos</td>
                <td>{{ number_format($totals['assets'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    <h2>Pasivos</h2>
    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Saldo</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($liabilities as $liability)
                <tr>
                    <td>{{ $liability['code'] }}</td>
                    <td>{{ $liability['name'] }}</td>
                    <td>{{ number_format($liability['saldo'], 2) }}</td>
                </tr>
            @endforeach
            <tr class="totals">
                <td colspan="2">Total Pasivos</td>
                <td>{{ number_format($totals['liabilities'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    <h2>Patrimonio</h2>
    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Saldo</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($equity as $item)
                <tr>
                    <td>{{ $item['code'] }}</td>
                    <td>{{ $item['name'] }}</td>
                    <td>{{ number_format($item['saldo'], 2) }}</td>
                </tr>
            @endforeach
            <tr class="totals">
                <td colspan="2">Total Patrimonio</td>
                <td>{{ number_format($totals['equity'], 2) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>