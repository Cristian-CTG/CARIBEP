<!DOCTYPE html>
@php
    use Modules\Factcolombia1\Models\Tenant\Company as CoCompany;
    use App\Models\Tenant\BankAccount;

    // Cargar empresa activa si no está definida
    $company = CoCompany::active();

    // Cargar banco/caja seleccionado si es solo un ID
    $bank_account = $filters['bank_account'] ?? null;
    if (is_numeric($bank_account)) {
        $bank_account = BankAccount::find($bank_account);
    }

    $auxiliar = $filters['auxiliar'] ?? '';
    $periodo = $filters['month'] ?? '';
    $logo_path = $company && $company->logo ? public_path("storage/uploads/logos/{$company->logo}") : null;
@endphp
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Libro de Bancos</title>
    <style>
        @page { margin: 15px; }
        html { font-family: Arial, sans-serif; font-size: 10px; }
        .header-table { width: 100%; margin-bottom: 10px; }
        .header-table td { vertical-align: top; border: none; }
        .company-title { font-size: 20px; font-weight: bold; color: #222; }
        .company-info { font-size: 11px; color: #444; line-height: 1.5; }
        .bank-info { font-size: 12px; color: #222; font-weight: bold; }
        .logo-box img { max-width: 120px; }
        .report-title { font-size: 18px; font-weight: bold; color: #0066cc; text-align: right; }
        .subtitle { font-size: 13px; font-weight: bold; color: #333; background: #e6f2ff; padding: 4px 0; }
        table { width: 100%; border-collapse: collapse; font-size: 9px; }
        th, td { border: 1px solid #333; padding: 4px; text-align: center; }
        th { background: #e6f2ff; color: #222; }
        .notes { font-size: 8px; margin-top: 10px; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .amount { font-family: "Courier New", monospace; }
        .total-row { background: #f2f2f2; font-weight: bold; }
        .logo-cell { width: 18%; }
        .company-cell { width: 52%; }
        .report-cell { width: 30%; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                @if($logo_path && file_exists($logo_path))
                    <div class="logo-box">
                        <img src="data:{{mime_content_type($logo_path)}};base64,{{base64_encode(file_get_contents($logo_path))}}" alt="Logo">
                    </div>
                @endif
            </td>
            <td class="company-cell">
                <div class="company-title">{{ $company->name ?? '' }}</div>
                <div class="company-info">
                    <div><strong>NIT:</strong> {{ $company->identification_number ?? $company->number ?? '' }}{{ $company->dv ? '-'.$company->dv : '' }}</div>
                    <div><strong>Dirección:</strong> {{ $company->address ?? '' }}</div>
                    <div><strong>Teléfono:</strong> {{ $company->phone ?? $company->telephone ?? '' }}</div>
                    <div><strong>Email:</strong> {{ $company->email ?? '' }}</div>
                    <div><strong>Régimen:</strong> {{ optional($company->type_regime)->name ?? '' }}</div>
                </div>
            </td>
            <td class="report-cell">
                <div class="report-title">LIBRO DE BANCOS</div>
                <div class="bank-info">
                    <div><strong>Banco:</strong> {{ $bank_account->description ?? 'Caja General' }}</div>
                    <div><strong>N° Cuenta:</strong> {{ $bank_account->number ?? '-' }}</div>
                    <div><strong>Auxiliar:</strong>
                        {{
                            $bank_account && isset($bank_account->chart_of_account)
                                ? $bank_account->chart_of_account->code
                                : ($auxiliar ?: '')
                        }}
                    </div>
                    <div><strong>Periodo:</strong> {{ $periodo }}</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="subtitle">Movimientos de la cuenta</div>

    <table>
        <thead>
            <tr>
                <th>FECHA</th>
                <th>DOCUMENTO</th>
                <th>METODO</th>
                <th>CONCEPTO</th>
                <th>TIPO</th>
                <th>N° COMPROBANTE</th>
                <th>DÉBITO</th>
                <th>CRÉDITO</th>
                <th>SALDO</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="8" class="text-right">SALDO INICIAL</td>
                <td class="amount">{{ number_format($saldo_inicial, 2, ',', '.') }}</td>
            </tr>
            @php $saldo = $saldo_inicial; @endphp
            @foreach($details as $detail)
                @php
                    $debit = $detail->debit ?? 0;
                    $credit = $detail->credit ?? 0;
                    $saldo += $debit - $credit;
                    $entry = $detail->journalEntry;
                    $account = $detail->chartOfAccount;
                    // Tipo de egreso
                    $tipo = '-';
                    if($debit > 0) $tipo = 'CI';
                    if($credit > 0) $tipo = 'CE';

                @endphp
                <tr>
                    <td>{{ $entry->date ?? '' }}</td>
                    <td>
                        {{ $entry->journal_prefix->prefix ?? '' }}-{{ $entry->number ?? '' }}
                    </td>
                    <td>
                        @php
                            $payment_name = $detail->payment_method_name ?? '';
                            $is_refund = false;
                            if($entry->description && (stripos($entry->description, 'devolución') !== false || stripos($entry->description, 'nota de crédito') !== false)) {
                                $is_refund = true;
                            }

                            // Si no hay método de pago en el detalle, busca en GlobalPayment
                            if(!$payment_name) {
                                $globalPayment = null;
                                if($entry->document && $entry->document->payments && $entry->document->payments->count()) {
                                    $payment = $entry->document->payments->first();
                                    $globalPayment = $payment->global_payment ?? null;
                                }
                                elseif($entry->purchase && $entry->purchase->payments && $entry->purchase->payments->count()) {
                                    $payment = $entry->purchase->payments->first();
                                    $globalPayment = $payment->global_payment ?? null;
                                }
                                elseif($entry->document_pos && $entry->document_pos->payments && $entry->document_pos->payments->count()) {
                                    $payment = $entry->document_pos->payments->first();
                                    $globalPayment = $payment->global_payment ?? null;
                                }
                                elseif($entry->support_document && $entry->support_document->payments && $entry->support_document->payments->count()) {
                                    $payment = $entry->support_document->payments->first();
                                    $globalPayment = $payment->global_payment ?? null;
                                }
                                elseif($entry->expense && $entry->expense->payments && $entry->expense->payments->count()) {
                                    $payment = $entry->expense->payments->first();
                                    $globalPayment = $payment->global_payment ?? null;
                                }
                                if($globalPayment && method_exists($globalPayment, 'payment') && $globalPayment->payment) {
                                    $payment_name = $globalPayment->payment->payment_method_name ?? '';
                                }
                            }
                        @endphp
                        {{ $is_refund ? 'DEVOLUCIÓN' : ($payment_name ?: 'TRANSFERENCIA') }}
                    </td>
                    <td class="text-left">{{ $entry->description ?? '' }}</td>
                    <td>{{ $tipo }}</td>
                    <td>{{ $entry ? $entry->getRelatedComprobanteNumber() : '-' }}</td>
                    <td class="amount">{{ number_format($debit, 2, ',', '.') }}</td>
                    <td class="amount">{{ number_format($credit, 2, ',', '.') }}</td>
                    <td class="amount">{{ number_format($saldo, 2, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="8" class="text-right">SALDO FINAL</td>
                <td class="amount">{{ number_format($saldo_final, 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

</body>
</html>