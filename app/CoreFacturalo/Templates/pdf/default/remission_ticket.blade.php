@php
    $establishment = $document->establishment;
    $customer = $document->customer;
    $paymentForm = $document->payment_form;
    $payments = $document->payments;
    $tittle = $document->prefix.'-'.str_pad($document->id, 8, '0', STR_PAD_LEFT);
    $total_payments = $payments->sum('payment');
    $is_paid = $total_payments == $document->total;
    $advanced_configuration = \Modules\Factcolombia1\Models\TenantService\AdvancedConfiguration::first();
    $seller_name = null;
    if($document->seller_id){
        $seller = \App\Models\Tenant\Seller::find($document->seller_id);
        $seller_name = $seller ? $seller->full_name : '';
    }
@endphp

<html>

<head>
</head>

<body style="margin-top:50px;">
    @if($is_paid)
    <div class="company_logo_box" style="position: absolute; text-align: center; top:25%">
        <img
            src="data:{{mime_content_type(public_path("status_images".DIRECTORY_SEPARATOR."pagado.png"))}};base64, {{base64_encode(file_get_contents(public_path("status_images".DIRECTORY_SEPARATOR."pagado.png")))}}"
            alt="pagado" style="opacity: 0.2;width: 90%;">
    </div>
    @endif

    @if ($company->logo && file_exists(public_path("storage/uploads/logos/{$company->logo}")))
        <img style="width: 136px; height: auto;" src="data:{{mime_content_type(public_path("storage/uploads/logos/{$company->logo}"))}};base64, {{base64_encode(file_get_contents(public_path("storage/uploads/logos/{$company->logo}")))}}" alt="{{$company->name}}" class="company_logo" style="max-width: 200px">
    @endif

    <!-- Encabezado -->
    <div style="width: 100%; text-align: center;">
        <table style="width: 100%; margin: 0 auto;">
            <tr>
                <td style="text-align: center;">
                    <p style="margin-bottom: 5px; font-size: 16px;">{{ $company->name }}</p>
                    <p style="margin: 5px 0; font-size: 12px;">NIT: {{ $company->identification_number }} - {{ $company->type_regime->name }}</p>
                    <p style="margin: 5px 0; font-size: 12px;">{{ $establishment->description }}</p>
                    <p style="margin: 5px 0; font-size: 12px;">{{$establishment->address != '-' ? $establishment->address : $company->address}} - {{$establishment->city->name ?? ''}} - {{$establishment->department->name ?? ''}} - {{$establishment->country->name ?? ''}}</p>
                    <p style="margin: 5px 0; font-size: 12px;">Telefono - {{$establishment->telephone}}</p>
                    <p style="margin: 5px 0; font-size: 12px;">E-mail: {{$establishment->email}}</p>
                    <p style="margin: 10px 0; font-size: 12px;">REMISIÓN No.{{ $document->number }}</p>
                    <!-- <p style="color: red; font-weight: bold; font-size: 14px; margin: 5px 0; border: 1px solid #000; padding: 5px 8px; display: inline-block; border-radius: 6px;">
                        {{ $document->number_full }}
                    </p> -->
                </td>
            </tr>
        </table>
    </div>

    <!-- Información del cliente -->
    <table class="full-width">
        <tr>
            <td class="align-top" style="width: 50%;">
                <table>
                    <tr>
                        <td style="font-size: 12px;">CC o NIT:</td>
                        <td style="font-size: 12px;">{{ $customer->number }}-{{ $customer->dv }}</td>
                    </tr>
                    <tr>
                        <td style="font-size: 12px;">Cliente:</td>
                        <td style="font-size: 12px;">{{ $customer->name }}</td>
                    </tr>
                    <tr>
                        <td style="font-size: 12px;">Regimen:</td>
                        <td style="font-size: 12px;">{{ $customer->type_regime->name }}</td>
                    </tr>
                    <tr>
                        <td style="font-size: 12px;">Dirección:</td>
                        <td style="font-size: 12px;">{{ $customer->address }}</td>
                    </tr>
                    <tr>
                        <td style="font-size: 12px;">Ciudad:</td>
                        <td style="font-size: 12px;">{{ $customer->city ? $customer->city->name : '' }} {{ $customer->country ? ' - '.$customer->country->name : '' }}</td>
                    </tr>
                    <tr>
                        <td style="font-size: 12px;">Teléfono:</td>
                        <td style="font-size: 12px;">{{ $customer->telephone }}</td>
                    </tr>
                    <tr>
                        <td style="font-size: 12px;">Email:</td>
                        <td style="font-size: 12px;">{{ $customer->email }}</td>
                    </tr>
                </table>
            </td>
            <td class="align-top">
                <table>
                    <tr>
                        <td style="font-size: 12px;">Forma de Pago:</td>
                        <td style="font-size: 12px;">{{ $paymentForm->name }}</td>
                    </tr>
                    <tr>
                        <td style="font-size: 12px;">Medio de Pago:</td>
                        <td style="font-size: 12px;">{{ $document->payment_method->name }}</td>
                    </tr>
                    @if(isset($seller_name) && $seller_name)
                        <tr>
                            <td>Vendedor:</td>
                            <td>{{ $seller_name }}</td>
                        </tr>
                    @endif
                    @if($document->time_days_credit)
                    <tr>
                        <td style="font-size: 12px;">Plazo Para Pagar:</td>
                        <td style="font-size: 12px;">{{ $document->time_days_credit }} Días</td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <!-- TABLA -->
    <table class="full-width mt-10 mb-10" style="border-top: 1px solid #000; border-bottom: 1px solid #000;">
        <thead>
            <tr>
                <th class="border-top-bottom desc-9 text-center">#</th>
                <th class="border-top-bottom desc-9 text-left">Código</th>
                <th class="border-top-bottom desc-9 text-left">Descripción</th>
                <th class="border-top-bottom desc-9 text-right">Cant.</th>
                <th class="border-top-bottom desc-9 text-right">V.Unit</th>
                <th class="border-top-bottom desc-9 text-right">IVA/IC</th>
                <th class="border-top-bottom desc-9 text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($document->items as $row)
                <tr>
                    <td class="text-center desc-9 align-top">{{ $loop->iteration }}</td>
                    <td class="text-left desc-9 align-top">{{ $row->item->internal_id }}</td>
                    <td class="text-left desc-9 align-top">
                        {!!$row->item->name!!} @if (!empty($row->item->presentation)) {!!$row->item->presentation->description!!} @endif
                    </td>
                    <td class="text-right desc-9 align-top">{{ number_format($row->quantity, 2) }}</td>
                    <td class="text-right desc-9 align-top">{{ number_format($row->unit_price, 2) }}</td>
                    <td class="text-right desc-9 align-top">{{ number_format($row->total_tax / $row->quantity, 2) }}</td>
                    <td class="text-right desc-9 align-top">{{ number_format($row->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <br>

    <!-- TOTAL -->
    <table class="full-width">
        <tr>
            <td width="60%"></td>
            <td width="40%">
                <table class="full-width">
                    <tr>
                        <td class="text-right desc-10" style="font-weight: bold;">SUBTOTAL:</td>
                        <td class="text-right desc-10" style="font-weight: bold;">{{ number_format($document->sale, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="text-right desc-10" style="font-weight: bold;">DESCUENTO (-):</td>
                        <td class="text-right desc-10" style="font-weight: bold;">{{ number_format($document->total_discount, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="text-right desc-10" style="font-weight: bold;">IMPUESTOS (+):</td>
                        <td class="text-right desc-10" style="font-weight: bold;">{{ number_format($document->total_tax, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="text-right desc-10" style="font-weight: bold;">TOTAL:</td>
                        <td class="text-right desc-10" style="font-weight: bold;">{{ number_format($document->total, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <br>

    <!-- OBSERVATIONES -->
    @if(!empty($document->observation))
    <div class="summarys">
        <div class="text-word" id="note">
            <p><strong>OBSERVACIONES:</strong></p>
            <p>{{ $document->observation }}</p>
        </div>
    </div>
    @endif

    @if($payments->count())
    <div class="summarys">
        <table class="full-width">
            <tr>
                <td><strong>PAGOS:</strong></td>
            </tr>
            @foreach($payments as $row)
            <tr>
                <td>{{ $row->date_of_payment->format('d/m/Y') }} {{ $row->reference }} {{ $row->payment }}</td>
            </tr>
            @endforeach
        </table>
    </div>
    @endif

    <!-- INFO -->
    <div class="summary">
        <div class="text-word" id="note">
            <p style="font-style: italic; font-size: 10px;">Informe el pago al teléfono {{ $establishment->telephone }} o al e-mail {{ $establishment->email }}</p>
        </div>
    </div>

    {{-- Mostrar cuentas bancarias si existen --}}
    @if(isset($document->bank_accounts) && $document->bank_accounts->count())
        <br>
        <table class="table" style="width: 100%; font-size: 11px; border: 1px solid #ccc;">
            <thead>
                <tr>
                    <th colspan="2" class="text-center" style="background: #f3f3f3;"><strong>Cuentas bancarias para pago</strong></th>
                </tr>
                <tr>
                    <th>Banco</th>
                    <th>Número de Cuenta</th>
                </tr>
            </thead>
            <tbody>
                @foreach($document->bank_accounts as $cuenta)
                    <tr>
                        <td>{{ $cuenta->bank->description ?? '' }}</td>
                        <td>{{ $cuenta->number }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <span style="font-size: 10px;">
            El pago debe realizarse únicamente a la cuenta bancaria indicada, no asumimos responsabilidad por consignaciones a otras cuentas.
        </span>
        <br>
    @endif

    {{-- Footer personalizado --}}
    @if($advanced_configuration && $advanced_configuration->custom_remission_footer_enabled && $advanced_configuration->custom_remission_footer_message)
        <div style="width:100%;text-align:center;margin-top:10px;font-size:11px;">
            {{ $advanced_configuration->custom_remission_footer_message }}
        </div>
    @endif

</body>
</html>