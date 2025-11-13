@php
    use Modules\Factcolombia1\Models\Tenant\Company as CoCompany;
    $company = CoCompany::active();
    $number = $journalEntry->journal_prefix->prefix . '-' . $journalEntry->number;
    $details = $journalEntry->details;
    $statuses = [ 'rejected' => 'Rechazado', 'draft' => 'Borrador', 'posted' => 'Aprobado'];
    $logo_path = public_path("storage/uploads/logos/{$company->logo}");
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title >Asiento contable {{ $number }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
        .header-table { width: 100%; margin-bottom: 15px; }
        .header-table td { vertical-align: top; border: none; }
        .company-title { font-size: 18px; font-weight: bold; color: #222; }
        .company-info { font-size: 11px; color: #444; line-height: 1.6; }
        .asiento-title { font-size: 16px; font-weight: bold; color: red; text-align: right; }
        .asiento-info { font-size: 12px; color: #333; line-height: 1.6; }
        .logo-box img { max-width: 120px; }
        .info-section { margin-bottom: 20px; border: 1px solid #ccc; padding: 10px; background-color: #f9f9f9; }
        .info-label { font-weight: bold; display: inline-block; width: 120px; color: #333; }
        .info-value { display: inline-block; color: #555; }
        .status { padding: 4px 8px; border: 1px solid #666; background-color: #eee; font-size: 12px; font-weight: bold; }
        table.details { width: 100%; border-collapse: collapse; margin-top: 20px; border: 1px solid #333; }
        table.details th { background-color: #ddd; border: 1px solid #333; padding: 8px; text-align: center; font-weight: bold; color: #000; }
        table.details td { border: 1px solid #666; padding: 7px; vertical-align: top; font-size: 10px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .account-code { font-family: "Courier New", monospace; font-weight: bold; color: #000; text-align: center; }
        .total-row { background-color: #eee; font-weight: bold; }
        .total-row td { border-top: 2px solid #333; padding: 10px 7px; }
        .amount { font-family: "Courier New", monospace; font-weight: normal; }
        .total-amount { font-family: "Courier New", monospace; font-weight: bold; color: #000; }
        .third-party-block { font-size: 9px; color: #444; margin-top: 2px; line-height: 1.5; }
        .third-party-label { font-weight: bold; display: inline-block; width: 90px; }
        .third-party-row { margin-bottom: 2px; }
        .details {
            page-break-inside: auto;
        }
        .details tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
        table.details {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            table-layout: fixed; /* ✅ evita que se expanda por contenido */
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="width: 20%; text-align: left;">
                @if($company->logo && file_exists($logo_path))
                    <div class="logo-box">
                        <img src="data:{{mime_content_type($logo_path)}};base64,{{base64_encode(file_get_contents($logo_path))}}" alt="Logo">
                    </div>
                @endif
            </td>
            <td style="width: 55%; padding-left: 1rem;">
                <div class="company-title">{{ $company->name ?? '' }}</div>
                <div class="company-info">
                    <div><strong>NIT:</strong> {{ $company->identification_number ?? $company->number ?? '' }}{{ $company->dv ? '-'.$company->dv : '' }}</div>
                    <div><strong>Dirección:</strong> {{ $company->address ?? '' }}</div>
                    <div><strong>Teléfono:</strong> {{ $company->phone ?? $company->telephone ?? '' }}</div>
                    <div><strong>Email:</strong> {{ $company->email ?? '' }}</div>
                    <div><strong>Régimen:</strong> {{ optional($company->type_regime)->name ?? '' }}</div>
                </div>
            </td>
            <td style="width: 25%; text-align: right;">
                <div class="asiento-title">ASIENTO CONTABLE</div>
                <div class="asiento-info">
                    <div><strong>Número:</strong> {{ $number }}</div>
                    <div><strong>Fecha:</strong> {{ $journalEntry->date }}</div>
                    <div><strong>Estado:</strong> <span class="status">{{ $statuses[$journalEntry->status] }}</span></div>
                </div>
            </td>
        </tr>
    </table>

    <div class="info-section">
        <span class="info-label">Descripción:</span>
        <span class="info-value">{{ $journalEntry->description }}</span>
    </div>

    @php
        $showBankOrPayment = $details->some(function($d) {
            return $d->bank_account_id || $d->payment_method_name;
        });
    @endphp

    <table class="details">
        <thead>
            @php
                $column_widths = $showBankOrPayment
                    ? ['12%', '20%', '26%', '8%', '16%', '9%', '9%'] // con banco/método
                    : ['12%', '24%', '34%', '10%', '10%', '10%'];     // sin banco/método
            @endphp
            <tr>
                <th style="width: {{ $column_widths[0] }}">Código de cuenta</th>
                <th style="width: {{ $column_widths[1] }}">Nombre de cuenta</th>
                <th style="width: {{ $column_widths[2] }}">Tercer Implicado</th>
                <th style="width: {{ $column_widths[3] }}">Tipo</th>
                @if($showBankOrPayment)
                    <th style="width: {{ $column_widths[4] }}">Banco/Caja y Método de Pago</th>
                @endif
                <th style="width: {{ $column_widths[$showBankOrPayment ? 5 : 4] }}" class="text-right">Debe</th>
                <th style="width: {{ $column_widths[$showBankOrPayment ? 6 : 5] }}" class="text-right">Haber</th>
            </tr>
        </thead>
        <tbody>
            @foreach($details as $detail)
                <tr>
                    <td class="account-code">{{ $detail->chartOfAccount->code }}</td>
                    <td style="text-align: left;">{{ $detail->chartOfAccount->name }}</td>
                    <td>
                        @if($detail->thirdParty)
                            <div class="third-party-block">
                                <div class="third-party-row">
                                    <span class="third-party-label">Nombre:</span> {{ $detail->thirdParty->name }}
                                </div>
                                @php
                                    $tipo_id = '';
                                    $tipo = $detail->thirdParty->type ?? '';
                                    if($tipo == 'employee' && isset($detail->thirdParty->document_type)) {
                                        $tipo_id = \Modules\Factcolombia1\Models\TenantService\PayrollTypeDocumentIdentification::where('code', $detail->thirdParty->document_type)->first();
                                        $tipo_id = $tipo_id ? $tipo_id->name : $detail->thirdParty->document_type;
                                    } elseif($tipo == 'seller' && isset($detail->thirdParty->document_type)) {
                                        $tipo_id = \Modules\Factcolombia1\Models\SystemService\TypeDocumentIdentification::where('code', $detail->thirdParty->document_type)->first();
                                        $tipo_id = $tipo_id ? $tipo_id->name : $detail->thirdParty->document_type;
                                    } elseif(($tipo == 'customers' || $tipo == 'suppliers') && isset($detail->thirdParty->document_type)) {
                                        $tipo_id = \Modules\Factcolombia1\Models\Tenant\TypeIdentityDocument::where('code', $detail->thirdParty->document_type)->first();
                                        $tipo_id = $tipo_id ? $tipo_id->name : $detail->thirdParty->document_type;
                                    }
                                @endphp
                                <div class="third-party-row">
                                    <span class="third-party-label">Tipo de identificación:</span> {{ $tipo_id }}
                                </div>
                                <div class="third-party-row">
                                    <span class="third-party-label">Documento:</span> {{ $detail->thirdParty->document }}
                                </div>
                                @if($detail->thirdParty->email)
                                    <div class="third-party-row">
                                        <span class="third-party-label">Email:</span> {{ $detail->thirdParty->email }}
                                    </div>
                                @endif
                                @if($detail->thirdParty->address)
                                    <div class="third-party-row">
                                        <span class="third-party-label">Dirección:</span> {{ $detail->thirdParty->address }}
                                    </div>
                                @endif
                                @if($detail->thirdParty->phone)
                                    <div class="third-party-row">
                                        <span class="third-party-label">Teléfono:</span> {{ $detail->thirdParty->phone }}
                                    </div>
                                @endif
                            </div>
                        @else
                            <span style="color:#bbb;">-</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($detail->debit > 0)
                            INGRESO
                        @elseif($detail->credit > 0)
                            EGRESO
                        @else
                            -
                        @endif
                    </td>
                    @if($showBankOrPayment)
                        <td>
                            @if($detail->bankAccount)
                                <div>
                                    <strong>Banco:</strong> {{ $detail->bankAccount->description }}@if($detail->bankAccount->number) - {{ $detail->bankAccount->number }}@endif
                                </div>
                            @elseif($detail->payment_method_name)
                                <div>
                                    <strong>Caja</strong>
                                </div>
                            @endif
                            @if($detail->payment_method_name)
                                <div>
                                    <strong>Método:</strong> {{ $detail->payment_method_name }}
                                </div>
                            @endif
                            @if(!$detail->bankAccount && !$detail->payment_method_name)
                                -
                            @endif
                        </td>
                    @endif
                    <td class="text-right amount">{{ number_format($detail->debit, 2, ',', '.') }}</td>
                    <td class="text-right amount">{{ number_format($detail->credit, 2, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="{{ $showBankOrPayment ? 5 : 4 }}" class="text-right">Total</td>
                <td class="text-right total-amount">{{ number_format($details->sum('debit'), 2, ',', '.') }}</td>
                <td class="text-right total-amount">{{ number_format($details->sum('credit'), 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>