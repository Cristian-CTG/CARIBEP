<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Tercero</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #333; padding: 5px; text-align: left; }
        th { background: #e6f2ff; }
        .header { margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Reporte de Tercero</h2>
        <strong>Tercero:</strong> {{ $third_name }}<br>
        <strong>Documento:</strong> {{ $third_document }}<br>
        <strong>Rango de fechas:</strong> {{ $start_date }} a {{ $end_date }}
    </div>
    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre de la cuenta</th>
                <th>Débito</th>
                <th>Crédito</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row['codigo'] }}</td>
                    <td>{{ $row['cuenta'] }}</td>
                    <td>{{ number_format($row['debito'], 2) }}</td>
                    <td>{{ number_format($row['credito'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">No hay movimientos para este tercero en el mes seleccionado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>