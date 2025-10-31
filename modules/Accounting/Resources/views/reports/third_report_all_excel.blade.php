<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Terceros</title>
</head>
<body>
    <div>
        <h3 align="center" class="title"><strong>Reporte de Terceros</strong></h3>
    </div>
    <br>
    <div style="margin-top:20px; margin-bottom:15px;">
        <table>
            <tr>
                <td><b>Tipo de tercero:</b></td>
                <td>{{ $tipo ?? '' }}</td>
                <td><b>Rango de fechas:</b></td>
                <td>{{ $start_date }} a {{ $end_date }}</td>
                <td><b>Fecha de reporte:</b></td>
                <td>{{ date('Y-m-d') }}</td>
            </tr>
        </table>
    </div>
    <br>
    @if(!empty($rows))
        <table border="1" cellspacing="0" cellpadding="5">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Nombre</th>
                    <th>Documento</th>
                    <th>Código</th>
                    <th>Cuenta</th>
                    <th>Débito</th>
                    <th>Crédito</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    <tr>
                        <td>{{ $row['tipo'] ?? '' }}</td>
                        <td>{{ $row['nombre'] }}</td>
                        <td>{{ $row['documento'] }}</td>
                        <td>{{ $row['codigo'] }}</td>
                        <td>{{ $row['cuenta'] }}</td>
                        <td>{{ number_format($row['debito'], 2) }}</td>
                        <td>{{ number_format($row['credito'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div>
            <p>No hay movimientos para los terceros seleccionados en el rango de fechas.</p>
        </div>
    @endif
</body>
</html>