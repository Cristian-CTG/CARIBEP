<?php

namespace Modules\Factcolombia1\Http\Controllers\Tenant;

use Facades\Modules\Factcolombia1\Models\Tenant\Document as FacadeDocument;
use Modules\Factcolombia1\Http\Requests\Tenant\DocumentRequest;
use Modules\Factcolombia1\Traits\Tenant\DocumentTrait;
use Modules\Factcolombia1\Http\Controllers\Controller;
use Modules\Factcolombia1\Models\TenantService\AdvancedConfiguration;
use Illuminate\Http\Request;
use Modules\Factcolombia1\Models\Tenant\{
    TypeIdentityDocument,
    DetailDocument,
    TypeDocument,
    TypeInvoice,
    NoteConcept,
    // Document,
    Currency,
    Company,
    Client,
    Item,
    Tax,
    PaymentMethod,
    PaymentForm,
    ConfigurationPurchaseCoupon,
    CustomerPurchaseCoupon,
};
use Carbon\Carbon;
use Mpdf\Mpdf;
use DB;
use Modules\Factcolombia1\Models\TenantService\{
    Company as ServiceTenantCompany
};
use Modules\Factcolombia1\Models\TenantService\{
    Company as TenantServiceCompany
};

use Modules\Factcolombia1\Models\TenantService\HealthTypeDocumentIdentification;
use Modules\Factcolombia1\Mail\Tenant\SendGraphicRepresentation;
use Illuminate\Support\Facades\Mail;
use DateTime;
use Storage;

use App\Models\Tenant\Item as ItemP;
use App\Models\Tenant\Person;
use App\Models\Tenant\Document; //replace model Document of module factcolombia1
use Modules\Inventory\Models\Warehouse as ModuleWarehouse;
use Modules\Document\Traits\SearchTrait;
use Modules\Factcolombia1\Http\Resources\Tenant\DocumentCollection;
use Modules\Factcolombia1\Http\Resources\Tenant\DocumentResource;
use App\Imports\CoDocumentsImport;
use App\Imports\CoDocumentsImportTwoFormat;
use Maatwebsite\Excel\Excel;
use Illuminate\Support\Facades\View;

use Modules\Factcolombia1\Helpers\DocumentHelper;
use Exception;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalPrefix;
use Modules\Accounting\Models\ChartOfAccount;
use Modules\Accounting\Models\ChartAccountSaleConfiguration;
use Modules\Accounting\Models\AccountingChartAccountConfiguration;
use Modules\Accounting\Helpers\AccountingEntryHelper;
use Modules\Accounting\Models\ThirdParty;
use Modules\Finance\Traits\FinanceTrait;
use App\Models\Tenant\BankAccount;
use App\Models\Tenant\Cash;


class DocumentController extends Controller
{
    use DocumentTrait, SearchTrait, FinanceTrait;

    const REGISTERED = 1;
    const ACCEPTED = 5;
    const REJECTED = 6;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        return view('factcolombia1::document.tenant.index');
    }


    public function columns()
    {
        return [
            'date_of_issue' => 'Fecha de emisión',
            'number' => 'Número',
            'customer' => 'Cliente',
        ];
    }

    public function co_import(Request $request)
    {
        if ($request->hasFile('file')) {
            try {
                $import = new CoDocumentsImport();
                $import->import($request->file('file'), null, Excel::XLSX);
                $data = $import->getData();
                return [
                    'success' => true,
                    'message' =>  __('app.actions.upload.success'),
                    'data' => $data
                ];
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'message' =>  "Error al cargar el archivo... ".$e->getMessage()
                ];
            }
        }
        return [
            'success' => false,
            'message' =>  __('app.actions.upload.error'),
        ];
    }

    public function records(Request $request)
    {
        $query = Document::query();
        // Filtro por comprobante (ignora otros filtros)
        if ($request->filled('comprobante')) {
            // Elimina espacios y convierte a mayúsculas para uniformidad
            $comprobante = strtoupper(str_replace(' ', '', $request->comprobante));

            // Permite guion opcional: separa prefijo y número
            if (strpos($comprobante, '-') !== false) {
                $parts = explode('-', $comprobante, 2);
            } else {
                // Busca el primer dígito para separar prefijo y número
                $parts = preg_split('/(?=\d)/', $comprobante, 2);
            }

            if (count($parts) == 2 && $parts[0] !== '') {
                $prefix = $parts[0];
                $number = $parts[1];
                $query->where('prefix', $prefix)
                    ->where('number', $number);
            } else {
                
                // Solo número o solo prefijo
                if (is_numeric($comprobante)) {
                    $query->where('number', $comprobante);
                } else {
                    $query->where('prefix', $comprobante);
                }
            }
        } else {
            // Filtro por estado (state_document_id)
            if ($request->filled('state_document_id')) {
                $query->where('state_document_id', $request->state_document_id);
            }
            // Filtro por cliente (customer_id)
            if ($request->filled('customer_id')) {
                $query->where('customer_id', $request->customer_id);
            }
            // Filtro por resolución (type_document_id)
            if ($request->filled('resolution_id')) {
                $query->where('type_document_id', $request->resolution_id);
            }
            if ($request->column == 'date_of_issue') {

                if (!empty($request->fecha_inicio) && !empty($request->fecha_fin)) {
                    // Entre meses
                    $query->whereBetween('date_of_issue', [$request->fecha_inicio, $request->fecha_fin]);

                } else if (strlen($request->value) == 7) {
                    // Si el valor es un mes (YYYY-MM), filtrar por todo el mes
                    $year_month = explode('-', $request->value);
                    $year = $year_month[0];
                    $month = $year_month[1];

                    $query->whereYear('date_of_issue', $year)
                        ->whereMonth('date_of_issue', $month);
                } else if (!empty($request->value)) {
                    // Si es una fecha específica (YYYY-MM-DD)
                    $query->whereDate('date_of_issue', $request->value);
                }
            } else {
                $query->where($request->column, 'like', '%' . $request->value . '%');
            }
        }
        $query->whereTypeUser()->latest();

        return new DocumentCollection($query->paginate(config('tenant.items_per_page')));
    }

    //Para el listado de estados
    public function statesList()
    {
        // Ajusta el modelo y campos según tu estructura real
        return \Modules\Factcolombia1\Models\Tenant\StateDocument::select('id', 'name')->get();
    }

    //Para El listado de Resoluciones en la vista
    public function activeResolutions()
    {
        $resolutions = TypeDocument::whereNotNull('resolution_number')
            ->where('resolution_date_end', '>', now())
            ->orderBy('description')
            ->get(['id', 'description', 'prefix']);

        return response()->json($resolutions);
    }

    //Para el listado de clientes
    public function customersList()
    {
        $customers = Person::whereType('customers')
            ->whereIsEnabled()
            ->orderBy('name')
            ->get(['id', 'name']);
        return response()->json($customers);
    }


    public function record($id)
    {
        $record = new DocumentResource(Document::findOrFail($id));

        return $record;
    }

    public function create_unreferenced_note() {
        $note = null;
        $invoice = null;
        return view('factcolombia1::document.tenant.note', compact('note', 'invoice'));
    }

    public function create() {
        /*        $company = Company::with('type_regime', 'type_identity_document')->firstOrFail();
        return json_encode($company);   */

        return view('factcolombia1::document.tenant.create');
    }

    public function create_health() {
        return view('factcolombia1::document.tenant.create_health');
    }

    public function create_aiu() {
        /*        $company = Company::with('type_regime', 'type_identity_document')->firstOrFail();
        return json_encode($company);   */

        return view('factcolombia1::document.tenant.create_aiu');
    }


    public function note($id) {
        $note = Document::with(['items'])->findOrFail($id);
        $invoice = Document::with(['items'])->findOrFail($id);
        return view('factcolombia1::document.tenant.note', compact('note', 'invoice'));
    }

    public function duplicate_invoice($id){
        $invoice = Document::with(['items'])->findOrFail($id);
        return view('factcolombia1::document.tenant.duplicate', compact('invoice'));
    }

    public function edit_invoice($id){
        $invoice = Document::with(['items'])->findOrFail($id);
        return view('factcolombia1::document.tenant.edit', compact('invoice'));
    }

    /**
     * All
     * @return \Illuminate\Http\Response
     */
    public function all() {
        return [
            'payment_methods' => PaymentMethod::all(),
            'payment_forms' => PaymentForm::all(),
            'typeDocuments' => $typeDocuments = TypeDocument::query()
                ->get()
                ->each(function($typeDocument) {
                    $typeDocument->alert_range = (($typeDocument->to - 100) < (Document::query()
                        ->hasPrefix($typeDocument->prefix)
                        ->whereBetween('number', [$typeDocument->from, $typeDocument->to])
                        ->max('number') ?? $typeDocument->from));

                    $typeDocument->alert_date = ($typeDocument->resolution_date_end == null) ? false : Carbon::parse($typeDocument->resolution_date_end)->subMonth(1)->lt(Carbon::now());
                }),
            'typeInvoices' => TypeInvoice::all(),
            'documents' => Document::query()
                ->with('state_document', 'currency', 'type_document', 'detail_documents', 'reference', 'log_documents')
                ->get(),
            'currencies' => Currency::all(),
            'clients' => Client::all(),
            'items' => Item::query()
                ->with('typeUnit', 'tax')
                ->get(),
            'taxes' => Tax::all(),
            'companyservice' => ServiceTenantCompany::first()
        ];
    }

    public function sincronize_resolutions($identification_number){
        $resolutions = $this->api_conection("table/resolutions/{$identification_number}", "GET")->resolutions;
        foreach($resolutions as $resolution){
            if(in_array($resolution->type_document_id, [1, 2, 4, 5])){
                $r = TypeDocument::where('resolution_number', $resolution->resolution)->where('prefix', $resolution->prefix)->orderBy('resolution_date', 'desc')->get();
                if(count($r) == 0){
                    $rs = new TypeDocument();
                    $rs->template = "face_sincronize";
                    $rs->name = $resolution->type_document->name;
                    $rs->code = $resolution->type_document_id;
                    $rs->resolution_number = $resolution->resolution;
                    $rs->prefix = $resolution->prefix;
                    $rs->generated = null;
                    $rs->description = "SINCRONIZADA API";
                }
                else
                    $rs = $r[0];
                $rs->resolution_date = $resolution->resolution_date;
                $rs->resolution_date_end = $resolution->date_to;
                $rs->technical_key = $resolution->technical_key;
                $rs->from = $resolution->from;
                $rs->to = $resolution->to;
                $rs->save();
            }
        }
    }

    public function sincronize(Request $request)
    {
        try {
            $company = ServiceTenantCompany::firstOrFail();
            $this->sincronize_resolutions($company->identification_number);
            $base_url = config('tenant.service_fact');
            $i = 0;

            // Si viene tipo y fechas, sincroniza por fechas
            if ($request->type === 'fecha' && $request->filled(['desde', 'hasta'])) {
                $ch2 = curl_init("{$base_url}information/{$company->identification_number}/{$request->desde}/{$request->hasta}");
                curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "GET");
                curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch2, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Accept: application/json',
                    "Authorization: Bearer {$company->api_token}"
                ));
                $response_status = curl_exec($ch2);
                curl_close($ch2);

                $response_status_decoded = json_decode($response_status);
                if (isset($response_status_decoded->data[0]->documents)) {
                    $documents = $response_status_decoded->data[0]->documents;
                    foreach($documents as $document){
                        if($document->cufe != 'cufe-initial-number' && in_array($document->type_document_id, [1, 2, 4, 5])){
                            $d = Document::where('prefix', $document->prefix)->where('number', $document->number)->get();
                            if(count($d) == 0){
                                $this->store_sincronize($document);
                                $i++;
                            }
                        }
                    }
                }
                return [
                    "success" => true,
                    "message" => "Se sincronizaron satisfactoriamente, {$i} registros por rango de fechas.",
                ];
            }

            // Si viene tipo y página, sincroniza por página (comportamiento original)
            $advanced_configuration = AdvancedConfiguration::where('lastsync', '!=', 0)->get();
            if(count($advanced_configuration) > 0){
                $lastsync = 0;
            }
            else{
                $advanced_configuration = AdvancedConfiguration::where('lastsync', 0)->get();
                if(count($advanced_configuration) == 0){
                    $r = new AdvancedConfiguration();
                    $lastsync = 0;
                    $r->lastsync = $lastsync;
                    $r->minimum_salary = 0;
                    $r->transportation_allowance = 0;
                    $r->save();
                    $advanced_configuration = AdvancedConfiguration::where('lastsync', 0)->get();
                }
                else
                    $lastsync = 0;
            }

            // Si el usuario seleccionó página específica
            if ($request->type === 'pagina' && $request->filled('page')) {
                $lastsync = $request->page;
            }

            do{
                $ch2 = curl_init("{$base_url}information/{$company->identification_number}/page/{$lastsync}/page");
                curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "GET");
                curl_setopt($ch2, CURLOPT_POSTFIELDS, "");
                curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch2, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Accept: application/json',
                    "Authorization: Bearer {$company->api_token}"
                ));
                $response_status = curl_exec($ch2);
                curl_close($ch2);

                $response_status_decoded = json_decode($response_status);
                $documents = $response_status_decoded->data[0]->documents;
                foreach($documents as $document){
                    if($document->cufe != 'cufe-initial-number' && in_array($document->type_document_id, [1, 2, 4, 5])){
                        $d = Document::where('prefix', $document->prefix)->where('number', $document->number)->get();
                        if(count($d) == 0){
                            $this->store_sincronize($document);
                            $i++;
                        }
                    }
                }
                $lastsync++;
            }while($response_status_decoded->data[0]->count != 0 && $request->type !== 'pagina');
            $advanced_configuration[0]->lastsync = $lastsync;
            $advanced_configuration[0]->save();
            return [
                "success" => true,
                "message" => "Se sincronizaron satisfactoriamente, {$i} registros que se habian enviado directamente desde API...",
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => $this->getErrorFromException($e->getMessage(), $e),
            ];
        }
    }

    public function store_sincronize($document_invoice){
//        DB::connection('tenant')->beginTransaction();
//        try {
            $this->company = Company::query()->with('country', 'version_ubl', 'type_identity_document')->firstOrFail();
            $company = ServiceTenantCompany::firstOrFail();
                \Log::info('API request_api:', [
        'request_api' => $document_invoice->request_api
    ]);
            $invoice_json_decoded = json_decode($document_invoice->request_api, true);
            \Log::info('API request_api decoded:', $invoice_json_decoded);
//            \Log::debug($invoice_json_decoded);
//            \Log::debug($invoice_json_decoded);
    if(isset($invoice_json_decoded['invoice_lines'])){
        \Log::info('API invoice_lines:', $invoice_json_decoded['invoice_lines']);
    }
            $correlative_api = $invoice_json_decoded['number'];
            $service_invoice = $invoice_json_decoded;
            if (isset($invoice_json_decoded['health_fields'])){
                if (isset($invoice_json_decoded['health_fields']['invoice_period_start_date']) && isset($invoice_json_decoded['health_fields']['invoice_period_end_date']))
                {
                    $service_invoice['health_fields']['invoice_period_start_date'] = $invoice_json_decoded['health_fields']['invoice_period_start_date'];
                    $service_invoice['health_fields']['invoice_period_end_date'] = $invoice_json_decoded['health_fields']['invoice_period_end_date'];
                    $service_invoice['health_fields']['health_type_operation_id'] = 1;
                    $service_invoice['health_fields']['users_info'] = $invoice_json_decoded['health_fields']['users_info'];
                }
            }
//\Log::debug($service_invoice);
            $resolution = TypeDocument::where('resolution_number', $service_invoice['resolution_number'])->where('prefix', $service_invoice['prefix'])->orderBy('resolution_date', 'desc')->get();
            $nextConsecutive = FacadeDocument::nextConsecutive($resolution[0]->id);
            if($document_invoice !== NULL){
                $request = new Request();
                $request->type_document_id = $resolution[0]->id;
                $request->resolution_id = $resolution[0]->id;
                $request->type_invoice_id = $resolution[0]->code;

                $customer = (object)$service_invoice['customer'];
                $p = Person::where('number', $customer->identification_number)->get();
                if(count($p) == 0){
                    $person = new Person();
                    $person->type = 'customers';
                    $person->dv = $customer->dv ? $customer->dv : NULL;
                    $person->type_regime_id = $customer->type_regime_id ? $customer->type_regime_id : 2;
                    $person->type_person_id = $customer->type_organization_id ? $customer->type_organization_id : 2;
                    $person->type_obligation_id = isset($customer->type_liability_id) ? $customer->type_liability_id : 117;
                    $person->identity_document_type_id = $customer->type_document_identification_id;
                    $person->number = $customer->identification_number;
                    $person->code = $customer->identification_number;
                    $person->name = $customer->name;
                    $person->country_id = 47;
                    $person->department_id = 779;
                    $person->city_id = 12688;
                    $person->address = $customer->address;
                    $person->email = $customer->email;
                    $person->telephone = $customer->phone;
                    $person->save();
                    $request->customer_id = $person->id;
                }
                else
                    $request->customer_id = $p[0]->id;

                $request->currency_id = 170;
                $request->date_expiration = $service_invoice['payment_form']['payment_due_date'];
                $request->date_issue = $service_invoice['date'];
                $request->observation = (key_exists('notes', $service_invoice)) ? $service_invoice['notes'] : "";
                $request->sale = $service_invoice['legal_monetary_totals']['line_extension_amount'];
                $request->total = $service_invoice['legal_monetary_totals']['payable_amount'];
                if(isset($service_invoice['legal_monetary_totals']['allowance_total_amount']))
                    $request->total_discount = $service_invoice['legal_monetary_totals']['allowance_total_amount'];
                else
                    $request->total_discount = 0;
                $request->taxes = Tax::all();
                foreach ($request->taxes as $tax) {
                    $tax->total = 0;
                    $tax->retention = 0;
                }
                if (isset($service_invoice['tax_totals']) && is_array($service_invoice['tax_totals'])) {
                    foreach ($service_invoice['tax_totals'] as $tax_total) {

                        $tax_found = null;
                        foreach ($request->taxes as $tax) {
                            if (
                                $tax->type_tax_id == $tax_total['tax_id'] &&
                                floatval($tax->rate) == floatval($tax_total['percent']) &&
                                (
                                    ($tax->type_tax_id == 1 && $tax->code == '71') ||
                                    ($tax->type_tax_id != 1)
                                )
                            ) {
                                $tax_found = $tax;
                                break;
                            }
                        }

                        if (!$tax_found) {
                            $tax_data = [
                                'name' => isset($tax_total['name']) ? $tax_total['name'] : 'Impuesto automático',
                                'code' => ($tax_total['tax_id'] == 1) ? '71' : (isset($tax_total['code']) ? $tax_total['code'] : ''),
                                'rate' => $tax_total['percent'],
                                'conversion' => 100,
                                'is_percentage' => true,
                                'is_fixed_value' => false,
                                'is_retention' => false,
                                'in_base' => false,
                                'in_tax' => null,
                                'type_tax_id' => $tax_total['tax_id'],
                                'chart_account_sale' => null,
                                'chart_account_purchase' => null,
                                'chart_account_return_sale' => null,
                                'chart_account_return_purchase' => null,
                            ];
                            $tax_found = Tax::create($tax_data);
                            $tax_found->total = 0;
                            $tax_found->retention = 0;
                            $request->taxes->push($tax_found);
                        }

                        $tax_found->total += isset($tax_total['tax_amount']) ? floatval($tax_total['tax_amount']) : 0;
                    }
                }
                $request->total_tax = $service_invoice['legal_monetary_totals']['tax_inclusive_amount'] - $service_invoice['legal_monetary_totals']['line_extension_amount'];
                $request->subtotal = $service_invoice['legal_monetary_totals']['line_extension_amount'];
                $request->payment_form_id = $service_invoice['payment_form']['payment_form_id'];
                $request->payment_method_id = $service_invoice['payment_form']['payment_method_id'];
                $request->time_days_credit = $service_invoice['payment_form']['duration_measure'];
                $request->xml = $document_invoice->xml;
                $request->cufe = $document_invoice->cufe;
                $request->order_reference = [];
                if(isset($service_invoice['health_fields'])){
                    $request->health_fields = $service_invoice['health_fields'];
                    $request->health_users = $service_invoice['health_fields']['users_info'];
                }
                else{
                    $request->health_fields = [];
                    $request->health_users = [];
                }
                $request->items = $service_invoice['invoice_lines'];
            }
            $response = json_encode(['cufe' => $request->cufe]);
            $response_status = NULL;
            $request->state_document_id = 5; // Estado aceptado
            \Log::info('TAXES CALCULADOS ANTES DE GUARDAR:', [
    'taxes' => $request->taxes
]);
            $this->document = DocumentHelper::createDocument($request, $nextConsecutive, $correlative_api, $this->company, $response, $response_status, $company->type_environment_id);
            (new DocumentHelper())->savePayments($this->document, [], $request);
//        } catch (\Exception $e) {
//            DB::connection('tenant')->rollBack();
//            return [
//                'success' => false,
//                'message' => $e->getMessage(),
//                'line' => $e->getLine(),
//                'trace' => $e->getTrace(),
//            ];
//        }
//        DB::connection('tenant')->commit();

        // Crear asiento contable después de crear el documento
        if ($this->document->type_document_id == 3) { // Nota de crédito
            $this->registerAccountingCreditNoteEntries($this->document);
            $this->registerAccountingCreditNotePaymentRefund($this->document);
        } elseif ($this->document->type_document_id == 2) { // Nota de débito
            // Si tienes un método para notas de débito, llámalo aquí
            $this->registerAccountingSaleEntries($this->document);
        } else { // Factura normal
            $this->registerAccountingSaleEntries($this->document);
        }

        $document_helper = new DocumentHelper();
        $document_helper->updateStateDocument(self::ACCEPTED, $this->document);
        return [
            'success' => true,
            'data' => [
                'id' => $this->document->id
            ]
        ];
    }

    /**
     * Consultar zipkey - usado en habilitación
     *
     * @param  Request $request
     * @return array
     */
    public function queryZipkey(Request $request)
    {
        try {
            $document = Document::findOrFail($request->id);
            $response_api = json_decode($document->response_api);
            $zip_key = $response_api->ResponseDian->Envelope->Body->SendTestSetAsyncResponse->SendTestSetAsyncResult->ZipKey ?? null;
            $document_helper = new DocumentHelper();

            // aceptado
            // $zip_key = "b6bfa75d-3f26-4b8b-8b65-3d1b547445e0";

            // rechazo
            // $zip_key = "9cb17852-6401-4d98-829c-132a74b63386";

            if($zip_key)
            {
                $company = ServiceTenantCompany::select('api_token')->whereFilterWithOutAllRelations()->firstOrFail();
                $correlative_api = $document->number;
                $base_url = config('tenant.service_fact');

                $ch2 = curl_init("{$base_url}ubl2.1/status/zip/{$zip_key}");

                curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, 0);

                if(file_exists(storage_path('sendmail.api')))
                    curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode(array("sendmail" => true, "is_payroll" => false, "is_eqdoc" => false)));
                else
                    curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode(array("sendmail" => false, "is_payroll" => false, "is_eqdoc" => false)));

                curl_setopt($ch2, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Accept: application/json',
                    "Authorization: Bearer {$company->api_token}"
                ));

                $response_status = curl_exec($ch2);
                curl_close($ch2);

                $response_status_decoded = json_decode($response_status);
                // dd($response_status_decoded);

                $type_document_service = $document->getTypeDocumentService();
                $document_type_description = ($type_document_service == '1') ? 'Factura' : 'Nota';

                if(property_exists($response_status_decoded, 'ResponseDian')){

                    $dian_response = $response_status_decoded->ResponseDian->Envelope->Body->GetStatusZipResponse->GetStatusZipResult->DianResponse;

                    if($dian_response->IsValid == "true"){

                        // api
                        $this->setStateDocument($type_document_service, $correlative_api);

                        //actualizar datos del documento aceptado
                        $message_zip_key = "{$dian_response->StatusCode} - {$dian_response->StatusDescription} - {$dian_response->StatusMessage}";
                        $document_helper->updateStateDocument(self::ACCEPTED, $document);
                        $document_helper->updateMessageQueryZipkey($message_zip_key, $document);

                        return $document_helper->responseMessage(true, $message_zip_key);

                    }
                    else
                    {

                        $extract_error_zip_key = $dian_response->ErrorMessage->string ?? $dian_response->StatusDescription;
                        $error_message_zip_key = is_array($extract_error_zip_key) ?  implode(",", $extract_error_zip_key) : $extract_error_zip_key;

                        //excepcion
                        $status_code = $dian_response->StatusCode ?? [];
                        // $status_code = [];

                        // 'Batch en proceso de validación.'
                        if(empty($status_code)){
                            $document_helper->throwException("Error al Validar {$document_type_description} Nro: {$correlative_api} Errores: {$error_message_zip_key}");
                        }

                        //estado rechazado
                        $document_helper->updateStateDocument(self::REJECTED, $document);
                        $document_helper->updateMessageQueryZipkey($error_message_zip_key, $document);

                        return $document_helper->responseMessage(false, "Error al Validar {$document_type_description} Nro: {$correlative_api} Errores: {$error_message_zip_key}");

                    }
                }
                else{

                    $error_message = $response_status_decoded->message;

                    return $document_helper->responseMessage(false, "Error al Validar {$document_type_description} Nro: {$correlative_api} Errores: ".$error_message);

                }
            }
            else{

                return $document_helper->responseMessage(false, "Error de ZipKey.");

            }


        } catch (Exception $e)
        {
            return $this->getErrorFromException($e->getMessage(), $e);
        }

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  App\Http\Controllers\Tenant\DocumentRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(DocumentRequest $request, $invoice_json = NULL){
        // \Log::debug($invoice_json);
        // dd($request->all());
        // ini_set('memory_limit', '-1');
        DB::connection('tenant')->beginTransaction();
        try {
            if($invoice_json !== NULL)
                $invoice_json_decoded = json_decode($invoice_json, true);
            if(!$request->customer_id && $invoice_json === NULL){
                $customer = (object)$request->service_invoice['customer'];
                $person = Person::updateOrCreate([
                    'type' => 'customers',
                    'identity_document_type_id' => $customer->identity_document_type_id,
                    'number' => $customer->identification_number,
                ], [
                    'code' => random_int(1000, 9999),
                    'name' => $customer->name,
                    'country_id' => 47,
                    'department_id' => 779,
                    'city_id' => 12688,
                    'address' => $customer->address,
                    'email' => $customer->email,
                    'telephone' => $customer->phone,
                ]);
                $request['customer_id'] = $person->id;
            }

            $response =  null;
            $response_status =  null;
            // $correlative_api = $this->getCorrelativeInvoice(1, $request->prefix);
            $this->company = Company::query()->with('country', 'version_ubl', 'type_identity_document')->firstOrFail();

            // if (($this->company->limit_documents != 0) && (Document::count() >= $this->company->limit_documents))
            //     return [
            //         'success' => false,
            //         'message' => '"Has excedido el límite de documentos de tu cuenta."'
            //     ];

            $company = ServiceTenantCompany::firstOrFail();

            // si la empresa esta en habilitacion, envio el parametro ignore_state_document_id en true
            // para buscar el correlativo en api sin filtrar por el campo state_document_id=1

            $ignore_state_document_id = ($company->type_environment_id === 2 || $invoice_json !== NULL);
            $ignore_state_document_id = true;

            // Modificar la lógica para manejar edición
            if ($request->is_edit) {
                // Buscar el documento original
                $originalDocument = Document::where('number', $request->number)
                                         ->where('prefix', $request->prefix)
                                         ->first();

                if ($originalDocument) {
                    // Modificar el prefijo del documento original agregando "B"
                    $originalDocument->prefix = 'B' . $originalDocument->prefix;
                    $originalDocument->save();

                    // Mantener el prefijo original para el nuevo documento
                    $correlative_api = $this->getCorrelativeInvoice(1, $request->prefix, $ignore_state_document_id);
                }
            } else {
                if($invoice_json !== NULL) {
                    $correlative_api = $invoice_json_decoded['number'];
                } else {
                    $correlative_api = $this->getCorrelativeInvoice(1, $request->prefix, $ignore_state_document_id);
                }
            }

            // dd($correlative_api);
            // \Log::debug($correlative_api);
            if(isset($request->number))
                $correlative_api = $request->number;

            if(!is_numeric($correlative_api)){
                return [
                    'success' => false,
                    'message' => 'Error al obtener correlativo Api.'
                ];
            }
            // \Log::debug($invoice_json_decoded);

            if($invoice_json !== NULL)
                $service_invoice = $invoice_json_decoded;
            else
                $service_invoice = $request->service_invoice;

            // Agregar cuentas bancarias si vienen en el request
            if ($request->has('bank_accounts')) {
                $service_invoice['bank_accounts'] = $request->bank_accounts;
            }
            //agregar campos personalizados para la plantilla
            if ($request->filled('head_note')) {
                $service_invoice['head_note'] = $request->head_note;
            }
            if ($request->filled('foot_note')) {
                $service_invoice['foot_note'] = $request->foot_note;
            }
            $service_invoice['is_tirilla2'] = $request->input('is_tirilla2', false);
            // if ($request->filled('notes')) {
            //     $service_invoice['notes'] = $request->notes;
            // }

            if($invoice_json === NULL){
                $service_invoice['number'] = $correlative_api;
                $service_invoice['prefix'] = $request->prefix;
                $service_invoice['resolution_number'] = $request->resolution_number;
                if($request->format_print != "2"){
                    $service_invoice['foot_note'] = "Modo de operación: Software Propio - by ".env('APP_NAME', 'FACTURADOR')." La presente Factura Electrónica de Venta, es un título valor de acuerdo con lo establecido en el Código de Comercio y en especial en los artículos 621,772 y 774. El Decreto 2242 del 24 de noviembre de 2015 y el Decreto Único 1074 de mayo de 2015. El presente título valor se asimila en todos sus efectos a una letra de cambio Art. 779 del Código de Comercio. Con esta el Comprador declara haber recibido real y materialmente las mercancías o prestación de servicios descritos en este título valor.";
                }
            }
            //\Log::debug(json_encode($service_invoice));
            $service_invoice['web_site'] = env('APP_NAME', 'FACTURADOR');
            //\Log::debug(json_encode($service_invoice));
            if(!is_null($this->company['jpg_firma_facturas']))
              if(file_exists(public_path('storage/uploads/logos/'.$this->company['jpg_firma_facturas']))){
                  $firma_facturacion = base64_encode(file_get_contents(public_path('storage/uploads/logos/'.$this->company['jpg_firma_facturas'])));
                  $service_invoice['firma_facturacion'] = $firma_facturacion;
              }

            if(file_exists(storage_path('logo_empresa_emisora.jpg'))){
                $logo_empresa_emisora = base64_encode(file_get_contents(storage_path('logo_empresa_emisora.jpg')));
                $service_invoice['logo_empresa_emisora'] = $logo_empresa_emisora;
            }

            if ($request->order_reference)
            {
                if (isset($request['order_reference']['issue_date_order']) && isset($request['order_reference']['id_order']))
                {
                    $service_invoice['order_reference']['id_order'] = $request['order_reference']['id_order'];
                    $service_invoice['order_reference']['issue_date_order'] = $request['order_reference']['issue_date_order'];
                }
            }
            if($invoice_json === NULL){
                if ($request->health_fields){
                    if (isset($request['health_fields']['invoice_period_start_date']) && isset($request['health_fields']['invoice_period_end_date']))
                    {
                        $service_invoice['health_fields']['invoice_period_start_date'] = $request['health_fields']['invoice_period_start_date'];
                        $service_invoice['health_fields']['invoice_period_end_date'] = $request['health_fields']['invoice_period_end_date'];
                        $service_invoice['health_fields']['health_type_operation_id'] = 1;
                        $service_invoice['health_fields']['users_info'] = $request->health_users;
                    }
                }
            }
            else{
                if (isset($invoice_json_decoded['health_fields'])){
                    if (isset($invoice_json_decoded['health_fields']['invoice_period_start_date']) && isset($invoice_json_decoded['health_fields']['invoice_period_end_date']))
                    {
                        $service_invoice['health_fields']['invoice_period_start_date'] = $invoice_json_decoded['health_fields']['invoice_period_start_date'];
                        $service_invoice['health_fields']['invoice_period_end_date'] = $invoice_json_decoded['health_fields']['invoice_period_end_date'];
                        $service_invoice['health_fields']['health_type_operation_id'] = 1;
                        $service_invoice['health_fields']['users_info'] = $invoice_json_decoded['users_info'];
                    }
                }
            }

            $datoscompany = Company::with('type_regime', 'type_identity_document')->firstOrFail();
            if(file_exists(storage_path('template.api'))){
                $service_invoice['invoice_template'] = "one";
                $service_invoice['template_token'] = password_hash($company->identification_number, PASSWORD_DEFAULT);
            }
            else{
                if($request->format_print != "2"){
                    $service_invoice['invoice_template'] = $request->format_print;
                    $service_invoice['template_token'] = password_hash($company->identification_number, PASSWORD_DEFAULT);
                }
            }

            $sucursal = \App\Models\Tenant\Establishment::where('id', auth()->user()->establishment_id)->first();

            if(file_exists(storage_path('sendmail.api')))
                $service_invoice['sendmail'] = true;
            $service_invoice['ivaresponsable'] = $datoscompany->type_regime->name;
            $service_invoice['establishment_name'] = $sucursal->description;
            if($sucursal->address != '-')
                $service_invoice['establishment_address'] = $sucursal->address;
            if($sucursal->telephone != '-')
                $service_invoice['establishment_phone'] = $sucursal->telephone;
            if(!is_null($sucursal->establishment_logo))
                if(file_exists(public_path('storage/uploads/logos/'.$sucursal->id."_".$sucursal->establishment_logo))){
                    $establishment_logo = base64_encode(file_get_contents(public_path('storage/uploads/logos/'.$sucursal->id."_".$sucursal->establishment_logo)));
                    $service_invoice['establishment_logo'] = $establishment_logo;
                }
            if(!is_null($sucursal->email))
                $service_invoice['establishment_email'] = $sucursal->email;
            $service_invoice['nombretipodocid'] = $datoscompany->type_identity_document->name;
            $service_invoice['tarifaica'] = $datoscompany->ica_rate;
            $service_invoice['actividadeconomica'] = $datoscompany->economic_activity_code;
            if($invoice_json === NULL){
                $service_invoice['notes'] = $request->observation;
                $service_invoice['date'] = date('Y-m-d', strtotime($request->date_issue));
                $service_invoice['time'] = date('H:i:s');
                $service_invoice['payment_form']['payment_form_id'] = $request->payment_form_id;
                $service_invoice['payment_form']['payment_method_id'] = $request->payment_method_id;
                if($request->payment_form_id == '1')
                    $service_invoice['payment_form']['payment_due_date'] = date('Y-m-d');
                else
                    $service_invoice['payment_form']['payment_due_date'] = date('Y-m-d', strtotime($request->date_expiration));
                $service_invoice['payment_form']['duration_measure'] = $request->time_days_credit;
            }
            if ($request->has('seller')) {
                $service_invoice['seller'] = $request->seller;
            }
            $service_invoice['customer']['dv'] = $this->validarDigVerifDIAN($service_invoice['customer']['identification_number']);
            // $service_invoice['legal_monetary_totals']['line_extension_amount'] = "2350000.00";

            $id_test = $company->test_id;
            $base_url = config('tenant.service_fact');

            if($company->type_environment_id == 2 && $company->test_id != 'no_test_set_id'){
            //     \Log::debug("Alexander");
                $ch = curl_init("{$base_url}ubl2.1/invoice/{$id_test}");
            }
            else
                $ch = curl_init("{$base_url}ubl2.1/invoice");

            $data_document = json_encode($service_invoice);
            // dd($data_document);
            //\Log::debug("{$base_url}ubl2.1/invoice");
            //\Log::debug($company->api_token);
            //\Log::debug($correlative_api);
            //\Log::debug($data_document);
            //            return $data_document;
            //return "";
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS,($data_document));
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Accept: application/json',
                "Authorization: Bearer {$company->api_token}"
            ));
            $response = curl_exec($ch);
            if(config('tenant.show_log')) {
                \Log::debug('DocumentController:715: '.$response);
            }
            curl_close($ch);
            $response_model = json_decode($response);
            $zip_key = null;
            $invoice_status_api = null;

            if(isset($response_model->success) && !$response_model->success) {
                return [
                    'success' => false,
                    'validation_errors' => true,
                    'message' =>  $response_model->message,
                ];
            }

            if($company->type_environment_id == 2 && $company->test_id != 'no_test_set_id'){
                if(array_key_exists('urlinvoicepdf', $response_model) && array_key_exists('urlinvoicexml', $response_model))
                {
                    if(!is_string($response_model->ResponseDian->Envelope->Body->SendTestSetAsyncResponse->SendTestSetAsyncResult->ZipKey))
                    {
                        if(is_string($response_model->ResponseDian->Envelope->Body->SendTestSetAsyncResponse->SendTestSetAsyncResult->ErrorMessageList->XmlParamsResponseTrackId->Success))
                        {
                            if($response_model->ResponseDian->Envelope->Body->SendTestSetAsyncResponse->SendTestSetAsyncResult->ErrorMessageList->XmlParamsResponseTrackId->Success == 'false')
                            {
                                return [
                                    'success' => false,
                                    'message' => $response_model->ResponseDian->Envelope->Body->SendTestSetAsyncResponse->SendTestSetAsyncResult->ErrorMessageList->XmlParamsResponseTrackId->ProcessedMessage
                                ];
                            }
                        }
                    }
                    else
                        if(is_string($response_model->ResponseDian->Envelope->Body->SendTestSetAsyncResponse->SendTestSetAsyncResult->ZipKey))
                        {
                            $zip_key = $response_model->ResponseDian->Envelope->Body->SendTestSetAsyncResponse->SendTestSetAsyncResult->ZipKey;
                        }
                }

                // dd($response_model);
                //declaro variuable response status en null
                $response_status = null;
                //compruebo zip_key para ejecutar servicio de status document

                // ************* queryZipkey ************
                // if($zip_key)
                // {
                //     //espero 3 segundos para ejecutar sevcio de status document
                //     sleep(3);

                //     $ch2 = curl_init("{$base_url}ubl2.1/status/zip/{$zip_key}");
                //     curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                //     curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "POST");

                //     if(file_exists(storage_path('sendmail.api'))){
                //         curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode(array("sendmail" => true)));
                //     }
                //     curl_setopt($ch2, CURLOPT_HTTPHEADER, array(
                //         'Content-Type: application/json',
                //         'Accept: application/json',
                //         "Authorization: Bearer {$company->api_token}"
                //     ));
                //     $response_status = curl_exec($ch2);
                //     curl_close($ch2);
                //     $response_status_decoded = json_decode($response_status);


                //     if(property_exists($response_status_decoded, 'ResponseDian')){
                //         if($response_status_decoded->ResponseDian->Envelope->Body->GetStatusZipResponse->GetStatusZipResult->DianResponse->IsValid == "true")
                //             $this->setStateDocument(1, $correlative_api);
                //         else
                //         {
                //             if(is_array($response_status_decoded->ResponseDian->Envelope->Body->GetStatusZipResponse->GetStatusZipResult->DianResponse->ErrorMessage->string))
                //                 $mensajeerror = implode(",", $response_status_decoded->ResponseDian->Envelope->Body->GetStatusZipResponse->GetStatusZipResult->DianResponse->ErrorMessage->string);
                //             else
                //                 $mensajeerror = $response_status_decoded->ResponseDian->Envelope->Body->GetStatusZipResponse->GetStatusZipResult->DianResponse->ErrorMessage->string;
                //             if($response_status_decoded->ResponseDian->Envelope->Body->GetStatusZipResponse->GetStatusZipResult->DianResponse->IsValid == 'false')
                //             {
                //                 return [
                //                     'success' => false,
                //                     'message' => "Error al Validar Factura Nro: {$correlative_api} Errores: ".$mensajeerror
                //                 ];
                //             }
                //         }
                //     }
                //     else{
                //         $mensajeerror = $response_status_decoded->message;
                //         return [
                //             'success' => false,
                //             'message' => "Error al Validar Factura Nro: {$correlative_api} Errores: ".$mensajeerror
                //         ];

                //     }
                // }
                // else
                //     return [
                //         'success' => false,
                //         'message' => "Error de ZipKey."
                //     ];

                // ************* queryZipkey ************

            }
            else{
                if($response_model->ResponseDian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult->IsValid == "true")
                    $this->setStateDocument(1, $correlative_api);
                else
                {
                    if(is_array($response_model->ResponseDian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult->ErrorMessage->string))
                        $mensajeerror = implode(",", $response_model->ResponseDian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult->ErrorMessage->string);
                    else
                        $mensajeerror = $response_model->ResponseDian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult->ErrorMessage->string;
                    if($response_model->ResponseDian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult->IsValid == 'false'){
                    // if($invoice_json == NULL)
                    //     return [
                    //         'success' => false,
                    //         'message' => "Error al Validar Factura Nro: {$correlative_api} Errores: ".$mensajeerror
                    //    ];
                    }
                }
            }

            if($invoice_json === NULL)
                $nextConsecutive = FacadeDocument::nextConsecutive($request->type_document_id);
            else{
                $resolution = TypeDocument::where('resolution_number', $service_invoice['resolution_number'])->where('prefix', $service_invoice['prefix'])->orderBy('resolution_date', 'desc')->get();
                $nextConsecutive = FacadeDocument::nextConsecutive($resolution[0]->id);
            }
            $this->company = Company::query()->with('country', 'version_ubl', 'type_identity_document')->firstOrFail();
            // if(($this->company->limit_documents != 0) && (Document::count() >= $this->company->limit_documents))
            //     throw new \Exception("Has excedido el límite de documentos de tu cuenta.");
            if($invoice_json !== NULL){
                $request = new Request();
                $request->type_document_id = $resolution[0]->id;
                $request->resolution_id = $resolution[0]->id;
                $request->type_invoice_id = $resolution[0]->code;
                $request->customer_id = $service_invoice['customer']['customer_id'];
                $request->currency_id = 170;
                $request->date_expiration = $service_invoice['payment_form']['payment_due_date'];
                $request->date_issue = $service_invoice['date'];
                $request->observation = $service_invoice['notes'];
                $request->sale = $service_invoice['legal_monetary_totals']['payable_amount'];
                $request->total = $service_invoice['legal_monetary_totals']['payable_amount'];
                if(isset($service_invoice['legal_monetary_totals']['allowance_total_amount']))
                    $request->total_discount = $service_invoice['legal_monetary_totals']['allowance_total_amount'];
                else
                $request->total_discount = 0;
                $request->taxes = Tax::all();
                $request->total_tax = $service_invoice['legal_monetary_totals']['tax_inclusive_amount'] - $service_invoice['legal_monetary_totals']['line_extension_amount'];
                $request->subtotal = $service_invoice['legal_monetary_totals']['line_extension_amount'];
                $request->payment_form_id = $service_invoice['payment_form']['payment_form_id'];
                $request->payment_method_id = $service_invoice['payment_form']['payment_method_id'];
                $request->time_days_credit = $service_invoice['payment_form']['duration_measure'];
                $request->order_reference = [];
                if(isset($service_invoice['health_fields'])){
                    $request->health_fields = $service_invoice['health_fields'];
                    $request->health_users = $service_invoice['health_fields']['users_info'];
                }
                else{
                    $request->health_fields = [];
                    $request->health_users = [];
                }
                $request->items = $service_invoice['invoice_lines'];
            }

            if($response_model->ResponseDian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult->IsValid == 'true') {
                $state_document_id = self::ACCEPTED;
            } else {
                $state_document_id = self::REJECTED;
            }

            $request->merge(['state_document_id' => $state_document_id]);

            if ($request->has('seller_id')) {
                $request->merge(['seller_id' => $request->seller_id]);
            }

            $this->document = DocumentHelper::createDocument($request, $nextConsecutive, $correlative_api, $this->company, $response, $response_status, $company->type_environment_id);
            (new DocumentHelper())->savePayments($this->document, $request->payments,$request);

            // Registrar asientos contables
            $this->registerAccountingSaleEntries($this->document, $request);
            // Registrar cupón
            $this->registerCustomerCoupon($this->document);

        }
        catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            // Inicializar el mensaje de error
            $userFriendlyMessage = 'Ocurrió un error inesperado.';
            // Verificar si hay un mensaje de error específico en la respuesta de la API
            if (isset($response_model) && is_object($response_model) && isset($response_model->message)) {
                $userFriendlyMessage = $response_model->message;  // Mensaje general de la API
                // Verificar si hay detalles de errores específicos
                if (isset($response_model->errors) && is_object($response_model->errors)) {
                    $errorDetailsArray = []; // Cambia a array para mejorar eficiencia
                    foreach ($response_model->errors as $field => $errorMessages) {
                        if (is_array($errorMessages)) {
                            $errorDetailsArray[] = implode(', ', $errorMessages);
                        } else {
                            $errorDetailsArray[] = $errorMessages;
                        }
                    }
                    // Concatenar detalles de los errores al mensaje para el usuario
                    if (!empty($errorDetailsArray)) {
                        $userFriendlyMessage .= ' ' . implode(' ', $errorDetailsArray);
                    }
                }
            }
            // Obtener el mensaje de la excepción
            $errorMessage = $e->getMessage();
            // Verificar si el mensaje contiene "Undefined property: stdClass::$Response"
            if (strpos($errorMessage, 'Undefined property: stdClass::$Response') !== false) {
                // Si el mensaje contiene "Undefined property: stdClass::$Response", no mostrar nada
                $errorMessage = '';
            }
            // Devolver la respuesta con un mensaje de error más detallado
            \Log::error($e->getTrace());
            return [
                'success' => false,
                'validation_errors' => true,
                'message' =>  $errorMessage . ' ' . $userFriendlyMessage,
                'line' => $e->getLine(),
                // 'trace' => $e->getTrace(),
            ];
        }

        DB::connection('tenant')->commit();
        $this->company = Company::query()->with('country', 'version_ubl', 'type_identity_document')->firstOrFail();
        if (($this->company->limit_documents != 0) && (Document::count() >= $this->company->limit_documents - 10))
            $over = ", ADVERTENCIA, ha consumido ".Document::count()." documentos de su cantidad contratada de: ".$this->company->limit_documents;
        else
            $over = "";

        $document_helper = new DocumentHelper();
        if($response_model->ResponseDian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult->IsValid == 'true'){
            $document_helper->updateStateDocument(self::ACCEPTED, $this->document);
            return [
                'success' => true,
                'validation_errors' => false,
                'message' => "Se registro con éxito el documento #{$this->document->prefix}{$nextConsecutive->number}. {$over}",
                'data' => [
                    'id' => $this->document->id
                ]
            ];
        }
        else{
            $document_helper->updateStateDocument(self::REJECTED, $this->document);
            return [
                'success' => true,
                'validation_errors' => true,
                'message' => "Error al Validar Factura Nro: #{$this->document->prefix}{$nextConsecutive->number}., Sin embargo se guardo la factura para posterior envio, ... Errores: ".$mensajeerror." {$over}",
                'data' => [
                    'id' => $this->document->id
                ]
            ];
        }
    }

    private function registerAccountingSaleEntries($document, $request = null)
    {
        try {

            $config_accounts = AccountingChartAccountConfiguration::first();
            if (!$config_accounts) {
                \Log::error('No existe configuración contable global');
                return;
            }

            // Cuentas predeterminadas
            $accountIdCash = ChartOfAccount::where('code', '110505')->first();
            $accountDefaultIncome = ChartOfAccount::where('code', '413595')->first(); //$config_accounts->retained_earning_account
            $accountReceivableCustomer = ChartOfAccount::where('code', $config_accounts->customer_receivable_account)->first();

            if (!$accountIdCash || !$accountDefaultIncome || !$accountReceivableCustomer) {
                \Log::error('Faltan cuentas contables predeterminadas');
                return;
            }

            $document_type = TypeDocument::find($document->type_document_id);
            $is_credit = $document->payment_form_id == 2 ? true : false;

            // Obtener banco destino si aplica
            $payment_destination = $request ? $request->payment_destination_id : null;
            $bankAccount = null;
            $bankChartAccount = null;
            if (!$is_credit && $request->payment_destination_id && $request->payment_destination_id !== 'cash') {
                $bankAccount = BankAccount::where('id', $request->payment_destination_id)->first();
                if ($bankAccount && $bankAccount->chart_of_account_id) {
                    $bankChartAccount = ChartOfAccount::find($bankAccount->chart_of_account_id) ?? '1110';
                }
                if (!$bankChartAccount) {
                    $bankChartAccount = ChartOfAccount::where('code', '1110')->first();
                }
            }

            // Crear o recuperar el tercero contable
            $person = Person::find($document->customer_id);
            $thirdPartyId = null;
            $documentType = null;

            if ($person && $person->identity_document_type_id) {
                $typeDoc = TypeIdentityDocument::find($person->identity_document_type_id);
                $documentType = $typeDoc ? $typeDoc->code : null;
            }

            if ($person) {
                $thirdParty = ThirdParty::updateOrCreate(
                    ['document' => $person->number, 'type' => $person->type],
                    [
                        'name' => $person->name,
                        'email' => $person->email,
                        'address' => $person->address,
                        'phone' => $person->telephone,
                        'document_type' => $documentType,
                        'origin_id' => $person->id
                    ]
                );
                $thirdPartyId = $thirdParty->id;
            }

            $payment_method_name = !$is_credit ? 'Contado' : null;

            /**
             * Agrupar ítems por cuenta contable de ingreso
             */
            $items = $document->items()->with('relation_item.chart_account_sale_configuration')->get();

            $groupedAccounts = [];

            foreach ($items as $itemLine) {
                $item = $itemLine->relation_item;
                $quantity = (float) $itemLine->quantity;
                $subtotal = (float) ($itemLine->total - $itemLine->total_tax); // valor sin impuestos

                // Cuenta del ítem
                $incomeCode = null;

                if ($item && $item->chart_account_sale_configuration) {
                    $incomeCode = $item->chart_account_sale_configuration->income_account;
                }

                // Si el producto no tiene cuenta asignada, usar la predeterminada
                if (!$incomeCode && $accountDefaultIncome) {
                    $incomeCode = $accountDefaultIncome->code;
                }

                if (!$incomeCode) {
                    \Log::warning('Producto sin cuenta de ingreso ni cuenta por defecto: ' . ($item ? $item->name : 'Sin nombre'));
                    continue;
                }

                if (!isset($groupedAccounts[$incomeCode])) {
                    $groupedAccounts[$incomeCode] = 0;
                }

                $groupedAccounts[$incomeCode] += $subtotal;
            }

            // Construir movimientos contables
            $movements = [];

            // Débito: Caja o CxC (por el total)
            $movements[] = [
                'account_id' => $is_credit
                    ? $accountReceivableCustomer->id
                    : (
                        $bankChartAccount
                            ? $bankChartAccount->id
                            : $accountIdCash->id
                    ),
                'debit' => $document->total,
                'credit' => 0,
                'affects_balance' => true,
                'third_party_id' => $thirdPartyId,
                'payment_method_name' => $payment_method_name,
                'bank_account_id' => $bankAccount ? $bankAccount->id : null,
            ];

            // Créditos agrupados por cuenta de ingreso
            foreach ($groupedAccounts as $accountCode => $amount) {
                $account = ChartOfAccount::where('code', $accountCode)->first();
                if (!$account) {
                    \Log::warning("Cuenta contable no encontrada para código {$accountCode}");
                    continue;
                }

                $movements[] = [
                    'account_id' => $account->id,
                    'debit' => 0,
                    'credit' => $amount,
                    'affects_balance' => true,
                    'third_party_id' => $thirdPartyId,
                ];
            }

            // Registrar el asiento
            AccountingEntryHelper::registerEntry([
                'prefix_id' => 1,
                'description' => $document_type->name . ' #' . $document->prefix . '-' . $document->number,
                'document_id' => $document->id,
                'movements' => $movements,
                'taxes' => $document->taxes ?? [],
                'tax_config' => [
                    'tax_field' => 'chart_account_sale',
                    'tax_debit' => false,
                    'tax_credit' => true,
                    'retention_debit' => true,
                    'retention_credit' => false,
                    'third_party_id' => $thirdPartyId,
                ],
            ]);

        } catch (Exception $e) {
            \Log::error('Error creando asiento contable de venta: ' . $e->getMessage());
        }
    }

    public function preeliminarview(DocumentRequest $request){
        //        \Log::debug($invoice_json);
        try {
            if(!$request->customer_id){
                $customer = (object)$request->service_invoice['customer'];
                $person = Person::updateOrCreate([
                    'type' => 'customers',
                    'identity_document_type_id' => $customer->identity_document_type_id,
                    'number' => $customer->identification_number,
                ], [
                    'code' => random_int(1000, 9999),
                    'name' => $customer->name,
                    'country_id' => 47,
                    'department_id' => 779,
                    'city_id' => 12688,
                    'address' => $customer->address,
                    'email' => $customer->email,
                    'telephone' => $customer->phone,
                ]);
                $request['customer_id'] = $person->id;
            }

            $response = null;
            $this->company = Company::query()->with('country', 'version_ubl', 'type_identity_document')->firstOrFail();

            $company = ServiceTenantCompany::firstOrFail();

            // si la empresa esta en habilitacion, envio el parametro ignore_state_document_id en true
            // para buscar el correlativo en api sin filtrar por el campo state_document_id=1

            $ignore_state_document_id = ($company->type_environment_id === 2);
            $ignore_state_document_id = true;
            $correlative_api = $this->getCorrelativeInvoice(1, $request->prefix, $ignore_state_document_id);

            //            \Log::debug($correlative_api);

            if(!is_numeric($correlative_api)){
                return [
                    'success' => false,
                    'message' => 'Error al obtener correlativo Api.'
                ];
            }
            //            \Log::debug($invoice_json_decoded);

            $service_invoice = $request->service_invoice;

            $service_invoice['number'] = $correlative_api;
            $service_invoice['is_tirilla2'] = $request->input('is_tirilla2', false);
            $service_invoice['prefix'] = $request->prefix;
            $service_invoice['resolution_number'] = $request->resolution_number;
            $service_invoice['head_note'] = "V I S T A   P R E E L I M I N A R  --  V I S T A   P R E E L I M I N A R  --  V I S T A   P R E E L I M I N A R  --  V I S T A   P R E E L I M I N A R";
            $service_invoice['foot_note'] = "Modo de operación: Software Propio - by ".env('APP_NAME', 'FACTURADOR')." La presente Factura Electrónica de Venta, es un título valor de acuerdo con lo establecido en el Código de Comercio y en especial en los artículos 621,772 y 774. El Decreto 2242 del 24 de noviembre de 2015 y el Decreto Único 1074 de mayo de 2015. El presente título valor se asimila en todos sus efectos a una letra de cambio Art. 779 del Código de Comercio. Con esta el Comprador declara haber recibido real y materialmente las mercancías o prestación de servicios descritos en este título valor.";
            //\Log::debug(json_encode($service_invoice));
            $service_invoice['web_site'] = env('APP_NAME', 'FACTURADOR');
            //\Log::debug(json_encode($service_invoice));
            if(!is_null($this->company['jpg_firma_facturas']))
              if(file_exists(public_path('storage/uploads/logos/'.$this->company['jpg_firma_facturas']))){
                  $firma_facturacion = base64_encode(file_get_contents(public_path('storage/uploads/logos/'.$this->company['jpg_firma_facturas'])));
                  $service_invoice['firma_facturacion'] = $firma_facturacion;
              }

            if ($request->order_reference)
            {
                if (isset($request['order_reference']['issue_date_order']) && isset($request['order_reference']['id_order']))
                {
                    $service_invoice['order_reference']['id_order'] = $request['order_reference']['id_order'];
                    $service_invoice['order_reference']['issue_date_order'] = $request['order_reference']['issue_date_order'];
                }
            }
            if ($request->health_fields){
                if (isset($request['health_fields']['invoice_period_start_date']) && isset($request['health_fields']['invoice_period_end_date']))
                {
                    $service_invoice['health_fields']['invoice_period_start_date'] = $request['health_fields']['invoice_period_start_date'];
                    $service_invoice['health_fields']['invoice_period_end_date'] = $request['health_fields']['invoice_period_end_date'];
                    $service_invoice['health_fields']['health_type_operation_id'] = 1;
                    $service_invoice['health_fields']['users_info'] = $request->health_users;
                }
            }

            $datoscompany = Company::with('type_regime', 'type_identity_document')->firstOrFail();
            if(file_exists(storage_path('template.api'))){
                $service_invoice['invoice_template'] = "one";
                $service_invoice['template_token'] = password_hash($company->identification_number, PASSWORD_DEFAULT);
            }
            else{
                $service_invoice['invoice_template'] = $request->format_print;
                $service_invoice['template_token'] = password_hash($company->identification_number, PASSWORD_DEFAULT);
            }

            $sucursal = \App\Models\Tenant\Establishment::where('id', auth()->user()->establishment_id)->first();

            if(file_exists(storage_path('sendmail.api')))
                $service_invoice['sendmail'] = true;
            $service_invoice['ivaresponsable'] = $datoscompany->type_regime->name;
            $service_invoice['establishment_name'] = $sucursal->descriptioN;
            if($sucursal->address != '-')
                $service_invoice['establishment_address'] = $sucursal->address;
            if($sucursal->telephone != '-')
                $service_invoice['establishment_phone'] = $sucursal->telephone;
            if(!is_null($sucursal->establishment_logo))
                if(file_exists(public_path('storage/uploads/logos/'.$sucursal->id."_".$sucursal->establishment_logo))){
                    $establishment_logo = base64_encode(file_get_contents(public_path('storage/uploads/logos/'.$sucursal->id."_".$sucursal->establishment_logo)));
                    $service_invoice['establishment_logo'] = $establishment_logo;
                }
            if(!is_null($sucursal->email))
                $service_invoice['establishment_email'] = $sucursal->email;
            $service_invoice['nombretipodocid'] = $datoscompany->type_identity_document->name;
            $service_invoice['tarifaica'] = $datoscompany->ica_rate;
            $service_invoice['actividadeconomica'] = $datoscompany->economic_activity_code;
            $service_invoice['notes'] = $request->observation;
            $service_invoice['date'] = date('Y-m-d', strtotime($request->date_issue));
            $service_invoice['time'] = date('H:i:s');
            $service_invoice['payment_form']['payment_form_id'] = $request->payment_form_id;
            $service_invoice['payment_form']['payment_method_id'] = $request->payment_method_id;
            if($request->payment_form_id == '1')
                $service_invoice['payment_form']['payment_due_date'] = date('Y-m-d');
            else
                $service_invoice['payment_form']['payment_due_date'] = date('Y-m-d', strtotime($request->date_expiration));
            $service_invoice['payment_form']['duration_measure'] = $request->time_days_credit;
            $service_invoice['customer']['dv'] = $this->validarDigVerifDIAN($service_invoice['customer']['identification_number']);

            $base_url = config('tenant.service_fact');

            $ch = curl_init("{$base_url}ubl2.1/invoice/preeliminar-view");
            $data_document = json_encode($service_invoice);
            //\Log::debug("{$base_url}ubl2.1/invoice");
            //\Log::debug($company->api_token);
            //\Log::debug($correlative_api);
            //\Log::debug($data_document);
            //            return $data_document;
            //return "";
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS,($data_document));
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Accept: application/json',
                "Authorization: Bearer {$company->api_token}"
            ));
            $response = curl_exec($ch);
            //\Log::debug($response);
            curl_close($ch);
            $response_model = json_decode($response);
            // dd($response_model);
            return [
                'success' => true,
                'message' => $response_model->message,
                'base64invoicepdf' => $response_model->base64invoicepdf,
            ];

        }
        catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
            ];
        }
    }

    public function storeNote(DocumentRequest $request) {
        DB::connection('tenant')->beginTransaction();
        try {
            $note_service = $request->note_service;
            $url_name_note = '';
            $type_document_service = $note_service['type_document_id'];
            if( $type_document_service == 4){
                $url_name_note = 'credit-note';
            }
            elseif($type_document_service == 5){
                $url_name_note = 'debit-note';
            }

            $this->company = Company::query()
                ->with('country', 'version_ubl', 'type_identity_document')
                ->firstOrFail();

            // if (($this->company->limit_documents != 0) && (Document::count() >= $this->company->limit_documents))
            //     return [
            //             'success' => false,
            //             'message' => '"Has excedido el límite de documentos de tu cuenta."'
            //     ];

                // $correlative_api = $this->getCorrelativeInvoice($type_document_service);
            $company = ServiceTenantCompany::firstOrFail();

            //si la empresa esta en habilitacion, envio el parametro ignore_state_document_id en true
            //  para buscar el correlativo en api sin filtrar por el campo state_document_id=1
            $ignore_state_document_id = ($company->type_environment_id === 2);
            $correlative_api = $this->getCorrelativeInvoice($type_document_service, null, $ignore_state_document_id);
            // dd($correlative_api);

            if(!is_numeric($correlative_api)){
                return [
                    'success' => false,
                    'message' => 'Error al obtener correlativo Api.'
                ];
            }

            $note_service['number'] = $correlative_api;
            $note_service['date'] = date('Y-m-d', strtotime($request->date_issue));
            $note_service['time'] = date('H:i:s');

            $datoscompany = Company::with('type_regime', 'type_identity_document')->firstOrFail();
            // $company = ServiceTenantCompany::firstOrFail();

            $note_concept_id = NoteConcept::query()->where('id', $request->note_concept_id)->get();
            $note_service['discrepancyresponsecode'] = $note_concept_id[0]->code;
            $note_service['ivaresponsable'] = $datoscompany->type_regime->name;
            $note_service['nombretipodocid'] = $datoscompany->type_identity_document->name;
            $note_service['tarifaica'] = $datoscompany->ica_rate;
            $note_service['actividadeconomica'] = $datoscompany->economic_activity_code;
            $note_service['notes'] = $request->observation;
            $note_service['is_tirilla2'] = $request->input('is_tirilla2', false);
            $sucursal = \App\Models\Tenant\Establishment::where('id', auth()->user()->establishment_id)->first();

            if(file_exists(storage_path('sendmail.api')))
                $note_service['sendmail'] = true;
            $note_service['ivaresponsable'] = $datoscompany->type_regime->name;
            $note_service['establishment_name'] = $sucursal->description;
            if($sucursal->address != '-')
                $note_service['establishment_address'] = $sucursal->address;
            if($sucursal->telephone != '-')
                $note_service['establishment_phone'] = $sucursal->telephone;
            if (empty($note_service['customer']['email']) || !filter_var($note_service['customer']['email'], FILTER_VALIDATE_EMAIL)) {
                $note_service['customer']['email'] = $datoscompany->email;
            }
            $note_service['customer']['dv'] = $this->validarDigVerifDIAN($note_service['customer']['identification_number']);
            $note_service['foot_note'] = "Modo de operación: Software Propio - by ".env('APP_NAME', 'FACTURADOR');

            $id_test = $company->test_id;
            $base_url = config('tenant.service_fact');
            if($company->type_environment_id == 2 && $company->test_id != 'no_test_set_id')
                $ch = curl_init("{$base_url}ubl2.1/{$url_name_note}/{$id_test}");
            else
                $ch = curl_init("{$base_url}ubl2.1/{$url_name_note}");
            $data_document = json_encode($note_service);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS,($data_document));
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Accept: application/json',
                "Authorization: Bearer {$company->api_token}"
            ));
            $response = curl_exec($ch);
            curl_close($ch);
            //\Log::debug("{$base_url}ubl2.1/invoice");
            //\Log::debug($company->api_token);
            //\Log::debug($correlative_api);
            //\Log::debug($data_document);
            //            return $data_document;
            // \Log::debug($response);
            //return "";

            $response_model = json_decode($response);
            $zip_key = null;
            $invoice_status_api = null;
            $response_status = null;

            if($company->type_environment_id == 2 && $company->test_id != 'no_test_set_id'){
                if(array_key_exists('urlinvoicepdf', $response_model) && array_key_exists('urlinvoicexml', $response_model) )
                {
                    if(!is_string($response_model->ResponseDian->Envelope->Body->SendTestSetAsyncResponse->SendTestSetAsyncResult->ZipKey))
                    {
                        if(is_string($response_model->ResponseDian->Envelope->Body->SendTestSetAsyncResponse->SendTestSetAsyncResult->ErrorMessageList->XmlParamsResponseTrackId->Success))
                        {
                            if($response_model->ResponseDian->Envelope->Body->SendTestSetAsyncResponse->SendTestSetAsyncResult->ErrorMessageList->XmlParamsResponseTrackId->Success == 'false')
                            {
                                return [
                                    'success' => false,
                                    'message' => $response_model->ResponseDian->Envelope->Body->SendTestSetAsyncResponse->SendTestSetAsyncResult->ErrorMessageList->XmlParamsResponseTrackId->ProcessedMessage
                                ];
                            }
                        }
                    }
                    else
                    {
                        if(is_string($response_model->ResponseDian->Envelope->Body->SendTestSetAsyncResponse->SendTestSetAsyncResult->ZipKey))
                        {
                            $zip_key = $response_model->ResponseDian->Envelope->Body->SendTestSetAsyncResponse->SendTestSetAsyncResult->ZipKey;
                        }
                    }
                }

                //declaro variuable response status en null
                $response_status = null;
                //compruebo zip_key para ejecutar servicio de status document

                // ************* queryZipkey ************

                // if($zip_key)
                // {
                //     //espero 3 segundos para ejecutar sevcio de status document
                //     sleep(3);

                //     $ch2 = curl_init("{$base_url}ubl2.1/status/zip/{$zip_key}");
                //     curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                //     curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "POST");
                //     if(file_exists(storage_path('sendmail.api'))){
                //         curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode(array("sendmail" => true)));
                //     }
                //     curl_setopt($ch2, CURLOPT_HTTPHEADER, array(
                //         'Content-Type: application/json',
                //         'Accept: application/json',
                //         "Authorization: Bearer {$company->api_token}"
                //     ));
                //     $response_status = curl_exec($ch2);
                //     curl_close($ch2);

                //     $response_status_decoded = json_decode($response_status);
                //     if($response_status_decoded->ResponseDian->Envelope->Body->GetStatusZipResponse->GetStatusZipResult->DianResponse->IsValid == "true")
                //         $this->setStateDocument($type_document_service, $correlative_api);
                //     else
                //     {
                //         if(is_array($response_status_decoded->ResponseDian->Envelope->Body->GetStatusZipResponse->GetStatusZipResult->DianResponse->ErrorMessage->string))
                //             $mensajeerror = implode(",", $response_status_decoded->ResponseDian->Envelope->Body->GetStatusZipResponse->GetStatusZipResult->DianResponse->ErrorMessage->string);
                //         else
                //             $mensajeerror = $response_status_decoded->ResponseDian->Envelope->Body->GetStatusZipResponse->GetStatusZipResult->DianResponse->ErrorMessage->string;
                //         if($response_status_decoded->ResponseDian->Envelope->Body->GetStatusZipResponse->GetStatusZipResult->DianResponse->IsValid == 'false')
                //         {
                //             return [
                //                 'success' => false,
                //                 'message' => "Error al Validar Nota Nro: {$correlative_api} Errores: ".$mensajeerror
                //             ];
                //         }
                //     }
                // }
                // else
                //     return [
                //         'success' => false,
                //         'message' => "Error de ZipKey."
                //     ];

                // ************* queryZipkey ************

            }
            else{
                if($response_model->ResponseDian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult->IsValid == "true")
                    $this->setStateDocument($type_document_service, $correlative_api);
                else
                {
                    if(is_array($response_model->ResponseDian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult->ErrorMessage->string))
                        $mensajeerror = implode(",", $response_model->ResponseDian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult->ErrorMessage->string);
                    else
                        $mensajeerror = $response_model->ResponseDian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult->ErrorMessage->string;
                    if($response_model->ResponseDian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult->IsValid == 'false')
                    {
                        return [
                            'success' => false,
                            'message' => "Error al Validar Nota Nro: {$correlative_api} Errores: ".$mensajeerror
                        ];
                    }
                }
            }

            ///-------------------------------
            // dd($response_status, $response_model);
            $nextConsecutive = FacadeDocument::nextConsecutive($request->type_document_id);

            $this->company = Company::query()
                ->with('country', 'version_ubl', 'type_identity_document')
                ->firstOrFail();

            // if (($this->company->limit_documents != 0) && (Document::count() >= $this->company->limit_documents)) throw new \Exception("Has excedido el límite de documentos de tu cuenta.");


            if($response_model->ResponseDian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult->IsValid == 'true') {
                $state_document_id = self::ACCEPTED;
            } else {
                $state_document_id = self::REJECTED;
            }

            $request->merge(['state_document_id' => $state_document_id]);

            if ($request->has('seller_id')) {
                $request->merge(['seller_id' => $request->seller_id]);
            }
            
            $this->document = DocumentHelper::createDocument($request, $nextConsecutive, $correlative_api, $this->company, $response, $response_status, $company->type_environment_id);
            $this->document->update([
                'xml' => $this->getFileName(),
                'cufe' => $response_model->cude
            ]);

            // Registrar asientos contables
            if($this->document->type_document_id == 3 ){
                $this->registerAccountingCreditNoteEntries($this->document);
                $this->registerAccountingCreditNotePaymentRefund($this->document);
            }

            if ($request->reference_id && $this->document->type_document_id == 3) {
                $referenced_document = Document::find($request->reference_id);
                if ($referenced_document) {
                    DocumentHelper::handlePaymentsOnCreditNote(
                        $referenced_document,
                        $request->note_concept_id,
                        $request->total
                    );
                }
            }

            // Registrar asientos contables
            // if($this->document->type_document_id == 3 ){
            //     $this->registerAccountingCreditNoteEntries($this->document);
            //     $this->registerAccountingCreditNotePaymentRefund($this->document);
            // }
            if($this->document->type_document_id == 2 ){
                $this->registerAccountingSaleEntries($this->document);
            }

        }
        catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            // Inicializar el mensaje de error
            $userFriendlyMessage = 'Ocurrió un error inesperado.';
            // Verificar si hay un mensaje de error específico en la respuesta de la API
            if (isset($response_model->message)) {
                $userFriendlyMessage = $response_model->message;  // Mensaje general de la API
                // Verificar si hay detalles de errores específicos
                if (isset($response_model->errors) && is_object($response_model->errors)) {
                    $errorDetailsArray = []; // Cambia a array para mejorar eficiencia
                    foreach ($response_model->errors as $field => $errorMessages) {
                        if (is_array($errorMessages)) {
                            $errorDetailsArray[] = implode(', ', $errorMessages);
                        } else {
                            $errorDetailsArray[] = $errorMessages;
                        }
                    }
                    // Concatenar detalles de los errores al mensaje para el usuario
                    if (!empty($errorDetailsArray)) {
                        $userFriendlyMessage .= ' ' . implode(' ', $errorDetailsArray);
                    }
                }
            }
            // Obtener el mensaje de la excepción
            $errorMessage = $e->getMessage();
            // Verificar si el mensaje contiene "Undefined property: stdClass::$Response"
            if (strpos($errorMessage, 'Undefined property: stdClass::$Response') !== false) {
                // Si el mensaje contiene "Undefined property: stdClass::$Response", no mostrar nada
                $errorMessage = '';
            }
            // Devolver la respuesta con un mensaje de error más detallado
            return [
                'success' => false,
                'validation_errors' => true,
                'message' =>  $errorMessage . ' ' . $userFriendlyMessage,
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
            ];
        }

        DB::connection('tenant')->commit();

        $this->company = Company::query()
             ->with('country', 'version_ubl', 'type_identity_document')
             ->firstOrFail();
        if (($this->company->limit_documents != 0) && (Document::count() >= $this->company->limit_documents - 10))
            $over = ", ADVERTENCIA, ha consumido ".Document::count()." documentos de su cantidad contratada de: ".$this->company->limit_documents;
        else
            $over = "";

        return [
            'success' => true,
            'message' => "Se registro con éxito el documento #{$this->document->prefix}{$this->document->number}. {$over}",
            'data' => [
                'id' => $this->document->id
            ]
           //'data' => $data_document
        ];
    }

    private function registerAccountingCreditNoteEntries($document)
    {
        try {

            $accountConfiguration = AccountingChartAccountConfiguration::first();
            if (!$accountConfiguration) {
                \Log::error('No existe configuración contable global');
                return;
            }

            // Cuentas predeterminadas
            $accountCustomerReturns = ChartOfAccount::where('code', $accountConfiguration->customer_returns_account)->first(); // Clientes (CxC)
            $accountDefaultIncome = ChartOfAccount::where('code', '417505')->first(); // Ingreso por devoluciones
            if (!$accountCustomerReturns || !$accountDefaultIncome) {
                \Log::error('Faltan cuentas contables predeterminadas para nota de crédito');
                return;
            }

            $document_type = TypeDocument::find($document->type_document_id);
            $person = Person::find($document->customer_id);

            // Crear o recuperar tercero contable
            $thirdPartyId = null;
            $documentType = null;
            if ($person) {
                if ($person->identity_document_type_id) {
                    $typeDoc = TypeIdentityDocument::find($person->identity_document_type_id);
                    $documentType = $typeDoc ? $typeDoc->code : null;
                }

                $thirdParty = ThirdParty::updateOrCreate(
                    ['document' => $person->number, 'type' => $person->type],
                    [
                        'name' => $person->name,
                        'email' => $person->email,
                        'address' => $person->address,
                        'phone' => $person->telephone,
                        'document_type' => $documentType,
                        'origin_id' => $person->id
                    ]
                );
                $thirdPartyId = $thirdParty->id;
            }

            /**
             * Agrupar ítems por cuenta de ingreso configurada
             * (cada producto puede tener cuenta distinta)
             */
            $items = $document->items()->with('relation_item.chart_account_sale_configuration')->get();
            $groupedAccounts = [];

            foreach ($items as $itemLine) {
                $item = $itemLine->relation_item;
                $subtotal = (float) ($itemLine->total - $itemLine->total_tax);

                $incomeCode = null;
                if ($item && $item->chart_account_sale_configuration) {
                    $incomeCode = $item->chart_account_sale_configuration->sales_returns_account;
                }

                // Si no tiene cuenta definida, usar la de devoluciones genérica
                if (!$incomeCode && $accountDefaultIncome) {
                    $incomeCode = $accountDefaultIncome->code;
                }

                if (!$incomeCode) {
                    \Log::warning('Producto sin cuenta contable para nota de crédito: ' . ($item ? $item->name : 'Desconocido'));
                    continue;
                }

                if (!isset($groupedAccounts[$incomeCode])) {
                    $groupedAccounts[$incomeCode] = 0;
                }

                // Acumular subtotal neto (sin impuestos)
                $groupedAccounts[$incomeCode] += $subtotal;
            }

            /**
             * Construir movimientos contables
             * Créditos → disminuyen CxC del cliente
             * Débitos  → disminuyen ingresos (reversan venta)
             */
            $movements = [];

            // 1 Crédito a Clientes (reduce la CxC)
            $movements[] = [
                'account_id' => $accountCustomerReturns->id,
                'debit' => 0,
                'credit' => $document->total,
                'affects_balance' => true,
                'third_party_id' => $thirdPartyId,
            ];

            // 2 Débitos agrupados por cuentas de ingreso (reverso del ingreso)
            foreach ($groupedAccounts as $accountCode => $amount) {
                $account = ChartOfAccount::where('code', $accountCode)->first();
                if (!$account) {
                    \Log::warning("Cuenta no encontrada para código {$accountCode} en nota de crédito");
                    continue;
                }

                $movements[] = [
                    'account_id' => $account->id,
                    'debit' => $amount,
                    'credit' => 0,
                    'affects_balance' => true,
                    'third_party_id' => $thirdPartyId,
                ];
            }

            // Registrar asiento contable
            AccountingEntryHelper::registerEntry([
                'prefix_id' => 1,
                'description' => $document_type->name . ' #' . $document->prefix . '-' . $document->number,
                'document_id' => $document->id,
                'movements' => $movements,
                'taxes' => $document->taxes ?? [],
                'tax_config' => [
                    'tax_field' => 'chart_account_return_sale',
                    'tax_debit' => true,
                    'tax_credit' => false,
                    'retention_debit' => false,
                    'retention_credit' => true,
                    'third_party_id' => $thirdPartyId,
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Error creando asiento contable de nota de crédito: ' . $e->getMessage());
        }
    }

    private function registerAccountingCreditNotePaymentRefund($creditNote)
    {
        // 1. Buscar la factura afectada
        $invoice = $creditNote->reference; // referencia al documento original

        // 2. Buscar los pagos realizados a la factura
        $payments = $invoice->payments;

        // Validar: solo crear asiento si hay pagos
        if ($payments->isEmpty()) {
            return; // No hay pagos, no se crea asiento
        }

        // // 3. Configuración de cuentas
        // $config = AccountingChartAccountConfiguration::first();
        // $accountReceivable = ChartOfAccount::where('code', $config->customer_receivable_account)->first();

        // 3. Configuración de cuentas
        $config = AccountingChartAccountConfiguration::first();
        $accountReceivable = ChartOfAccount::where('code', $config->customer_receivable_account)->first();

        // 4. Obtener el cliente y el tercero
        $person = Person::find($invoice->customer_id);
        $thirdPartyId = null;
        $documentType = null;
        if ($person && $person->identity_document_type_id) {
            $typeDoc = TypeIdentityDocument::find($person->identity_document_type_id);
            $documentType = $typeDoc ? $typeDoc->code : null;
        }
        if ($person) {
            $thirdParty = ThirdParty::updateOrCreate(
                ['document' => $person->number, 'type' => $person->type],
                [
                    'name' => $person->name,
                    'email' => $person->email,
                    'address' => $person->address,
                    'phone' => $person->telephone,
                    'document_type' => $documentType,
                    'origin_id' => $person->id,
                ]
            );
            $thirdPartyId = $thirdParty->id;
        }

        foreach ($payments as $payment) {
            $accountDestinationID = null;
            $payment_method_name = 'Devolución'; // <-- aquí
            if ($payment->global_payment) {
                if ($payment->global_payment->destination_type === BankAccount::class) {
                    $bankAccount = BankAccount::find($payment->global_payment->destination_id);
                    $accountDestinationID = $bankAccount ? $bankAccount->chart_of_account_id : null;
                    $BankID = $bankAccount ? $bankAccount->id : null;
                    if (!$accountDestinationID) {
                        $accountDefaultBank = ChartOfAccount::where('code', '1110')->first();
                        $accountDestinationID = $accountDefaultBank ? $accountDefaultBank->id : null;
                    }
                } elseif ($payment->global_payment->destination_type === Cash::class) {
                    $accountCash = ChartOfAccount::where('code', '110505')->first();
                    $accountDestinationID = $accountCash ? $accountCash->id : null;
                }
            }

            // Generar asiento contable de devolución
            AccountingEntryHelper::registerEntry([
                'prefix_id' => 3, // Prefijo de devolución/nota de crédito
                'description' => 'Devolución de pago por Nota de Crédito ' . $creditNote->prefix . '-' . $creditNote->number,
                'document_id' => $creditNote->id,
                'movements' => [
                    [
                        'account_id' => $accountDestinationID,
                        'debit' => 0,
                        'credit' => $payment->payment, // Sale el dinero
                        'affects_balance' => true,
                        'third_party_id' => $thirdPartyId,
                        'bank_account_id' => $BankID ?? null,
                        'payment_method_name' => $payment_method_name,
                    ],
                    [
                        'account_id' => $accountReceivable->id,
                        'debit' => $payment->payment, // Se reduce la cuenta por cobrar
                        'credit' => 0,
                        'affects_balance' => true,
                        'third_party_id' => $thirdPartyId,
                        // 'payment_method_name' => $payment_method_name,
                    ],
                ],
            ]);
        }
    }

    /**
     * Download
     * @param  string   $type
     * @param  \App\Models\Tenant\Document $document
     * @return void
     */

     public function download($type, Document $document) {
        switch ($type) {
            case 'xml':

                return $this->downloadDocument($type, $document);

                break;
            case 'pdf':
                $mpdf = new Mpdf([
                    'tempDir' => storage_path('mpdf')
                ]);


                $servicecompany = TenantServiceCompany::firstOrFail();

                $mpdf->WriteHTML(view("pdf/{$document->type_document->template}", [
                    'typeIdentityDocuments' => TypeIdentityDocument::all(),
                    'company' => Company::firstOrFail(),
                    'servicecompany' => $servicecompany,
                    'document' => $document,

                ])->render());

                $mpdf->Output("{$document->prefix}{$document->number}.{$type}", 'D');
            default:
                throw new \Exception("The document does not exist", 1);

                break;
        }
    }


    public function sendEmailCoDocument(Request $request)
    {
        $company = ServiceTenantCompany::firstOrFail();
        $sucursal = \App\Models\Tenant\Establishment::where('id', auth()->user()->establishment_id)->first();
//        $send= (object)['number'=> $request->number, 'email'=> $request->email, 'number_full'=> $request->number_full];
        $prefix = substr($request->number_full, 0, strpos($request->number_full, '-'));
        $number = substr($request->number_full, strpos($request->number_full, '-') + 1);
//        \Log::debug($prefix);
//        \Log::debug($number);

        // 1. Procesar correos desde el request
        $emails = [];
        if (!empty($request->email)) {
            $emails = array_merge($emails, explode(';', $request->email));
        }
        if (!empty($request->additional_emails)) {
            $emails = array_merge($emails, explode(';', $request->additional_emails));
        }
        // Limpiar: quitar espacios, correos vacíos y duplicados
        $emails = array_unique(array_filter(array_map('trim', $emails)));
        // Filtrar correos válidos
        $emails = array_filter($emails, function ($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL);
        });

        if (empty($emails)) {
            return [
                'success' => false,
                'message' => 'Debe ingresar al menos un correo válido en los campos de correo.',
            ];
        }

        // 2. Construir email_cc_list con todos los correos válidos
        $email_cc_list = [];
        foreach ($emails as $email) {
            $email_cc_list[] = ['email' => $email];
        }

        // Agregar correo de la sucursal si es válido y no está ya en la lista
        if (!empty($sucursal->email) && filter_var($sucursal->email, FILTER_VALIDATE_EMAIL)) {
            if (!in_array($sucursal->email, $emails)) {
                $email_cc_list[] = ['email' => $sucursal->email];
            }
        }

        $send= (object)[
            'prefix' => $prefix,
            'number' => $number,
            'email_cc_list' => $email_cc_list
        ];
    //    \Log::debug(json_encode($send));
        $data_send = json_encode($send);

        $base_url = config('tenant.service_fact');
        $ch2 = curl_init("{$base_url}ubl2.1/send-email");
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch2, CURLOPT_POSTFIELDS,($data_send));
        curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch2, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
            "Authorization: Bearer {$company->api_token}"
        ));

        $response = curl_exec($ch2);
        \Log::debug($response);
        $respuesta = json_decode($response);
        curl_close($ch2);

        if(property_exists($respuesta, 'success'))
        {
            return [
                'success' => $respuesta->success,
                'message' => $respuesta->message
            ];
        }
        else{

            return [
                'success' => false,
                'message' => 'No se pudo enviar el correo.'
            ];

        }
    }


    public function sendEmail($number, $client)
    {
        /*$company = Company::firstOrFail();
        $document = Document::find($document);
        $client = Client::find($client);
        $servicecompany =  TenantServiceCompany::firstOrFail();
        $customer_email = $client->email;
        Mail::to($customer_email)->send(new SendGraphicRepresentation($company, $document, $servicecompany ));
        return [
            'success' => true,
            'message' => "Email enviado con éxito."
        ];*/
        $client = Client::find($client);

        $company = ServiceTenantCompany::firstOrFail();

        $send= (object)['number'=> $number, 'email'=> $client->email];
        $data_send = json_encode($send);

        $base_url = config('tenant.service_fact');
        $ch2 = curl_init("{$base_url}send_mail");
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch2, CURLOPT_POSTFIELDS,($data_send));
        curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch2, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
            "Authorization: Bearer {$company->api_token}"
        ));

        $response = curl_exec($ch2);
        $respuesta = json_decode($response);
        curl_close($ch2);

        if(property_exists($respuesta, 'success'))
        {
            return [
                'success' => $respuesta->success,
                'message' => $respuesta->message
            ];
        }
        else{

            return [
                'success' => false,
                'message' => 'No se puedo enviar el correo.'
            ];

        }
    }


    private function getBaseUrlCorrelativeInvoice($type_service, $prefix = null, $ignore_state_document_id = false)
    {
        $base_url = config('tenant.service_fact');
        $url = "{$base_url}ubl2.1/invoice/current_number/{$type_service}";

        if($ignore_state_document_id){

            $val_prefix = $prefix ? $prefix : 'null';
            $url .= "/{$val_prefix}/{$ignore_state_document_id}";

        }else{

            if($prefix){
                $url .= "/{$prefix}";
            }
        }

        return $url;
    }


    public function getCorrelativeInvoice($type_service, $prefix = null, $ignore_state_document_id = false)
    {
        try {
            if ($prefix) {
                $resolution = TypeDocument::where('prefix', $prefix)
                    ->where('code', $type_service)
                    ->orderBy('resolution_date', 'desc')
                    ->first();

                if ($resolution) {
                    $next_number = $resolution->generated + 1;

                    $document = Document::where('prefix', $prefix)
                        ->where('number', $next_number)
                        ->first();

                    if ($document) {
                        if ($document->state_document_id == 6) {
                            $document->delete();
                            return $next_number;
                        }
                    } else {
                        return $next_number;
                    }
                }
            }

            $company = ServiceTenantCompany::firstOrFail();

            $url = $this->getBaseUrlCorrelativeInvoice($type_service, $prefix, $ignore_state_document_id);
            $ch2 = curl_init($url);

            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch2, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Accept: application/json',
                "Authorization: Bearer {$company->api_token}"
            ));

            $response_data = curl_exec($ch2);
            $err = curl_error($ch2);
            curl_close($ch2);

            if ($err) {
                return null;
            }

            $response_encode = json_decode($response_data);

            if (isset($response_encode->exception)) {
                \Log::error($response_encode->trace);
                throw new \Exception($response_encode->message);
            }

            return $response_encode->number;

        } catch (\Exception $e) {
            return null;
        }
    }

    public function setStateDocument($type_service, $DocumentNumber)
    {
        $company = ServiceTenantCompany::firstOrFail();
        $base_url = config('tenant.service_fact');
        $ch2 = curl_init("{$base_url}ubl2.1/invoice/state_document/{$type_service}/{$DocumentNumber}");

        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch2, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
            "Authorization: Bearer {$company->api_token}"
        ));
        $response_data = curl_exec($ch2);
        $err = curl_error($ch2);
        curl_close($ch2);
        $response_encode = json_decode($response_data);
        if($err){
            return null;
        }
        else{
            return $response_encode;
        }
    }

    public function downloadxml($id)
    {
        $invoice =  Document::find($id);

        $api = json_decode($invoice->response_api);

        $data = base64_decode($api->invoicexml);

        $correlativo = $invoice->correlative_api;
        /*$file = fopen( storage_path('app')."/invoice/invoice-{$correlativo}.xml", "w");
        fwrite($file, $data . PHP_EOL);
        fclose($file);*/

        Storage::disk('tenant')->put("invoice_download/invoice-{$correlativo}.xml", $data );
        return Storage::disk('tenant')->download("invoice_download/invoice-{$correlativo}.xml");

       // return response()->download(storage_path("app/invoice/invoice-{$correlativo}.xml"));

    }

    public function tables()
    {
        $customers = $this->table('customers');
        $type_documents = TypeDocument::query()
            ->get()
            ->each(function($typeDocument) {
                $typeDocument->alert_range = (($typeDocument->to - 100) < (Document::query()
                    ->hasPrefix($typeDocument->prefix)
                    ->whereBetween('number', [$typeDocument->from, $typeDocument->to])
                    ->max('number') ?? $typeDocument->from));
                $typeDocument->alert_date = ($typeDocument->resolution_date_end == null) ? false : Carbon::parse($typeDocument->resolution_date_end)->subMonth(1)->lt(Carbon::now());
            });
        $payment_methods = PaymentMethod::all();
        $payment_forms = PaymentForm::all();
        $type_invoices = TypeInvoice::all();
        $currencies = Currency::all();
        $taxes = $this->table('taxes');

        $establishment_id = auth()->user()->establishment_id;

        $resolutions = TypeDocument::select('id','prefix', 'resolution_number', 'from', 'to', 'description', 'resolution_date_end', 'show_in_establishments', 'establishment_ids')
            ->whereNotNull('resolution_number')
            ->whereIn('code', [1,2,3])
            ->where('resolution_date_end', '>', Carbon::now())
            ->where(function($query) use ($establishment_id) {
                $query->where('show_in_establishments', 'all')
                    ->orWhere(function($q) use ($establishment_id) {
                        $q->where('show_in_establishments', 'custom')
                            ->whereJsonContains('establishment_ids', $establishment_id);
                    });
            })
            ->get();

        return compact('customers','payment_methods','payment_forms','type_invoices','currencies', 'taxes', 'type_documents', 'resolutions');
    }

    public function item_tables()
    {
        $items = $this->table('items');
        // $items =  Item::query()
        //     ->with('typeUnit', 'tax')
        //     ->get();

        $taxes = $this->table('taxes');

        $items_aiu  = $this->table('items_aiu');

        return compact('items', 'taxes', 'items_aiu');
    }

    private function api_conection($endpoint, $method, $payload = ""){
        $company = ServiceTenantCompany::firstOrFail();
        $base_url = config('tenant.service_fact');
        $ch = curl_init("{$base_url}{$endpoint}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
            "Authorization: Bearer {$company->api_token}"
        ));
        $response = curl_exec($ch);
        $response_model = json_decode($response);
        return $response_model;
    }

    public function health_tables()
    {
        $health_type_document_identifications = $this->api_conection("table/health_type_document_identifications", "GET")->health_type_document_identifications;
        $health_type_users = $this->api_conection("table/health_type_users", "GET")->health_type_users;
        $health_contracting_payment_methods = $this->api_conection("table/health_contracting_payment_methods", "GET")->health_contracting_payment_methods;
        $health_coverages = $this->api_conection("table/health_coverages", "GET")->health_coverages;

        return compact('health_type_document_identifications', 'health_type_users', 'health_contracting_payment_methods', 'health_coverages');
    }


    public function table($table)
    {
        if ($table === 'customers') {
            $customers = Person::whereType('customers')->whereIsEnabled()->orderBy('name')->get()->transform(function($row) {
                return [
                    'id' => $row->id,
                    'description' => $row->number.' - '.$row->name,
                    'name' => $row->name,
                    'number' => $row->number,
                    'identity_document_type_id' => $row->identity_document_type_id,
                    'address' =>  $row->address,
                    'email' =>  $row->email,
                    'telephone' =>  $row->telephone,
                    'type_person_id' => $row->type_person_id,
                    'type_regime_id' => $row->type_regime_id,
                    'city_id' => $row->city_id,
                    'type_obligation_id' => $row->type_obligation_id,
                    'dv' => $row->dv
                ];
            });
            return $customers;
        }

        if ($table === 'taxes') {
            return Tax::all()->transform(function($row) {
                return [
                    'id' => $row->id,
                    'name' => $row->name,
                    'code' => $row->code,
                    'rate' =>  $row->rate,
                    'conversion' =>  $row->conversion,
                    'is_percentage' =>  $row->is_percentage,
                    'is_fixed_value' =>  $row->is_fixed_value,
                    'is_retention' =>  isset($row->is_retention) ? $row->is_retention : false,
                    'in_base' =>  $row->in_base,
                    'in_tax' =>  $row->in_tax,
                    'type_tax_id' =>  $row->type_tax_id,
                    'type_tax' =>  $row->type_tax,
                    'retention' =>  0,
                    'total' =>  0,
                ];
            });
        }

        if ($table === 'items') {

            $establishment_id = auth()->user()->establishment_id;
            $warehouse = ModuleWarehouse::where('establishment_id', $establishment_id)->first();

            $items_u = ItemP::whereNotItemsAiu()->whereWarehouse()->whereIsActive()->whereNotIsSet()->orderBy('internal_id')->take(20)->get();
            $items_s = ItemP::whereNotItemsAiu()->where('unit_type_id','ZZ')->whereIsActive()->orderBy('internal_id')->take(10)->get();

           // $items_aiu = ItemP::whereIn('internal_id', ['aiu00001', 'aiu00002', 'aiu00003'])->get();

            $items = $items_u->merge($items_s);

            //$items = $items->merge($items_aiu);

            return collect($items)->transform(function($row) use($warehouse){
                $detail = $this->getFullDescription($row, $warehouse);
                $sale_unit_price_with_tax = $this->getSaleUnitPriceWithTax($row);
                return [
                    'id' => $row->id,
                    'name' => $row->name,
                    'full_description' => $detail['full_description'],
                    'brand' => $detail['brand'],
                    'category' => $detail['category'],
                    'stock' => $detail['stock'],
                    'internal_id' => $row->internal_id,
                    'description' => $row->description,
                    'currency_type_id' => $row->currency_type_id,
                    'currency_type_symbol' => $row->currency_type->symbol,
                    'sale_unit_price' => round($sale_unit_price_with_tax, 2),
                    'purchase_unit_price' => $row->purchase_unit_price,
                    'unit_type_id' => $row->unit_type_id,
                    'sale_affectation_igv_type_id' => $row->sale_affectation_igv_type_id,
                    'purchase_affectation_igv_type_id' => $row->purchase_affectation_igv_type_id,
                    'calculate_quantity' => (bool) $row->calculate_quantity,
                    'has_igv' => (bool) $row->has_igv,
                    'amount_plastic_bag_taxes' => $row->amount_plastic_bag_taxes,
                    'item_unit_types' => collect($row->item_unit_types)->transform(function($row) {
                        return [
                            'id' => $row->id,
                            'description' => "{$row->description}",
                            'item_id' => $row->item_id,
                            'unit_type_id' => $row->unit_type_id,
                            'unit_type' => $row->unit_type,
                            'quantity_unit' => $row->quantity_unit,
                            'price1' => $row->price1,
                            'price2' => $row->price2,
                            'price3' => $row->price3,
                            'price_default' => $row->price_default,
                        ];
                    }),
                    'warehouses' => collect($row->warehouses)->transform(function($row) use($warehouse){
                        return [
                            'warehouse_description' => $row->warehouse->description,
                            'stock' => $row->stock,
                            'warehouse_id' => $row->warehouse_id,
                            'checked' => ($row->warehouse_id == $warehouse->id) ? true : false,
                        ];
                    }),
                    'attributes' => $row->attributes ? $row->attributes : [],
                    'lots_group' => collect($row->lots_group)->transform(function($row){
                        return [
                            'id'  => $row->id,
                            'code' => $row->code,
                            'quantity' => $row->quantity,
                            'date_of_due' => $row->date_of_due,
                            'checked'  => false
                        ];
                    }),
                    'lots' => $row->item_lots->where('has_sale', false)->where('warehouse_id', $warehouse->id)->transform(function($row) {
                        return [
                            'id' => $row->id,
                            'series' => $row->series,
                            'date' => $row->date,
                            'item_id' => $row->item_id,
                            'warehouse_id' => $row->warehouse_id,
                            'has_sale' => (bool)$row->has_sale,
                            'lot_code' => ($row->item_loteable_type) ? (isset($row->item_loteable->lot_code) ? $row->item_loteable->lot_code:null):null
                        ];
                    }),
                    'lots_enabled' => (bool) $row->lots_enabled,
                    'series_enabled' => (bool) $row->series_enabled,
                    'unit_type' => $row->unit_type,
                    'tax' => $row->tax,
                    'active' => (bool) $row->active,
                ];
            });
        }

        if($table == 'items_aiu')
        {
            $establishment_id = auth()->user()->establishment_id;
            $warehouse = ModuleWarehouse::where('establishment_id', $establishment_id)->first();
            $items = ItemP::whereIn('internal_id', ['aiu00001', 'aiu00002', 'aiu00003'])->get();

            return collect($items)->transform(function($row) use($warehouse){
                $detail = $this->getFullDescription($row, $warehouse);
                return [
                    'id' => $row->id,
                    'name' => $row->name,
                    'full_description' => $detail['full_description'],
                    'brand' => $detail['brand'],
                    'category' => $detail['category'],
                    'stock' => $detail['stock'],
                    'internal_id' => $row->internal_id,
                    'description' => $row->description,
                    'currency_type_id' => $row->currency_type_id,
                    'currency_type_symbol' => $row->currency_type->symbol,
                    'sale_unit_price' => round($row->sale_unit_price, 2),
                    'purchase_unit_price' => $row->purchase_unit_price,
                    'unit_type_id' => $row->unit_type_id,
                    'sale_affectation_igv_type_id' => $row->sale_affectation_igv_type_id,
                    'purchase_affectation_igv_type_id' => $row->purchase_affectation_igv_type_id,
                    'calculate_quantity' => (bool) $row->calculate_quantity,
                    'has_igv' => (bool) $row->has_igv,
                    'amount_plastic_bag_taxes' => $row->amount_plastic_bag_taxes,
                    'item_unit_types' => collect($row->item_unit_types)->transform(function($row) {
                        return [
                            'id' => $row->id,
                            'description' => "{$row->description}",
                            'item_id' => $row->item_id,
                            'unit_type_id' => $row->unit_type_id,
                            'unit_type' => $row->unit_type,
                            'quantity_unit' => $row->quantity_unit,
                            'price1' => $row->price1,
                            'price2' => $row->price2,
                            'price3' => $row->price3,
                            'price_default' => $row->price_default,
                        ];
                    }),
                    'warehouses' => collect($row->warehouses)->transform(function($row) use($warehouse){
                        return [
                            'warehouse_description' => $row->warehouse->description,
                            'stock' => $row->stock,
                            'warehouse_id' => $row->warehouse_id,
                            'checked' => ($row->warehouse_id == $warehouse->id) ? true : false,
                        ];
                    }),
                    'attributes' => $row->attributes ? $row->attributes : [],
                    'lots_group' => collect($row->lots_group)->transform(function($row){
                        return [
                            'id'  => $row->id,
                            'code' => $row->code,
                            'quantity' => $row->quantity,
                            'date_of_due' => $row->date_of_due,
                            'checked'  => false
                        ];
                    }),
                    'lots' => $row->item_lots->where('has_sale', false)->where('warehouse_id', $warehouse->id)->transform(function($row) {
                        return [
                            'id' => $row->id,
                            'series' => $row->series,
                            'date' => $row->date,
                            'item_id' => $row->item_id,
                            'warehouse_id' => $row->warehouse_id,
                            'has_sale' => (bool)$row->has_sale,
                            'lot_code' => ($row->item_loteable_type) ? (isset($row->item_loteable->lot_code) ? $row->item_loteable->lot_code:null):null
                        ];
                    }),
                    'lots_enabled' => (bool) $row->lots_enabled,
                    'series_enabled' => (bool) $row->series_enabled,
                    'unit_type' => $row->unit_type,
                    'tax' => $row->tax,
                    'active' => (bool) $row->active,
                ];
            });
        }

        return [];
    }

    /**
     * Retorna el precio de venta mas impuesto asignado al producto
     *
     * @param  Item $item
     * @param  $decimal_quantity
     * @return double
     */
    private function getSaleUnitPriceWithTax($item)
    {
        $advanced_config = AdvancedConfiguration::first();
        $is_tax_included = $advanced_config->item_tax_included;
        if($is_tax_included) {
            return number_format($item->sale_unit_price * ( 1 + ($item->tax->rate ?? 0) / ($item->tax->conversion ?? 1)), 2, ".","");
        }
        return $item->sale_unit_price;
    }


    public function searchItems(Request $request)
    {
        $establishment_id = auth()->user()->establishment_id;
        $warehouse = ModuleWarehouse::where('establishment_id', $establishment_id)->first();

        $items_not_services = $this->getItemsNotServices($request);
        $items_services = $this->getItemsServices($request);
        $all_items = $items_not_services->merge($items_services);

        $items = collect($all_items)->transform(function($row) use($warehouse){

                $detail = $this->getFullDescription($row, $warehouse);
                $sale_unit_price_with_tax = $this->getSaleUnitPriceWithTax($row);

                return [
                    'id' => $row->id,
                    'name' => $row->name,
                    'full_description' => $detail['full_description'],
                    'brand' => $detail['brand'],
                    'category' => $detail['category'],
                    'stock' => $detail['stock'],
                    'internal_id' => $row->internal_id,
                    'description' => $row->description,
                    'currency_type_id' => $row->currency_type_id,
                    'currency_type_symbol' => $row->currency_type->symbol,
                    'sale_unit_price' => round($sale_unit_price_with_tax, 2),
                    'purchase_unit_price' => $row->purchase_unit_price,
                    'unit_type_id' => $row->unit_type_id,
                    'sale_affectation_igv_type_id' => $row->sale_affectation_igv_type_id,
                    'purchase_affectation_igv_type_id' => $row->purchase_affectation_igv_type_id,
                    'calculate_quantity' => (bool) $row->calculate_quantity,
                    'has_igv' => (bool) $row->has_igv,
                    'amount_plastic_bag_taxes' => $row->amount_plastic_bag_taxes,
                    'item_unit_types' => collect($row->item_unit_types)->transform(function($row) {
                        return [
                            'id' => $row->id,
                            'description' => "{$row->description}",
                            'item_id' => $row->item_id,
                            'unit_type_id' => $row->unit_type_id,
                            'unit_type' => $row->unit_type,
                            'quantity_unit' => $row->quantity_unit,
                            'price1' => $row->price1,
                            'price2' => $row->price2,
                            'price3' => $row->price3,
                            'price_default' => $row->price_default,
                        ];
                    }),
                    'warehouses' => collect($row->warehouses)->transform(function($row) use($warehouse){
                        return [
                            'warehouse_description' => $row->warehouse->description,
                            'stock' => $row->stock,
                            'warehouse_id' => $row->warehouse_id,
                            'checked' => ($row->warehouse_id == $warehouse->id) ? true : false,
                        ];
                    }),
                    'attributes' => $row->attributes ? $row->attributes : [],
                    'lots_group' => collect($row->lots_group)->transform(function($row){
                        return [
                            'id'  => $row->id,
                            'code' => $row->code,
                            'quantity' => $row->quantity,
                            'date_of_due' => $row->date_of_due,
                            'checked'  => false
                        ];
                    }),
                    'lots' => $row->item_lots->where('has_sale', false)->where('warehouse_id', $warehouse->id)->transform(function($row) {
                        return [
                            'id' => $row->id,
                            'series' => $row->series,
                            'date' => $row->date,
                            'item_id' => $row->item_id,
                            'warehouse_id' => $row->warehouse_id,
                            'has_sale' => (bool)$row->has_sale,
                            'lot_code' => ($row->item_loteable_type) ? (isset($row->item_loteable->lot_code) ? $row->item_loteable->lot_code:null):null
                        ];
                    }),
                    'lots_enabled' => (bool) $row->lots_enabled,
                    'series_enabled' => (bool) $row->series_enabled,
                    'unit_type' => $row->unit_type,
                    'tax' => $row->tax,
                    'is_set' => (bool) $row->is_set,
                    'active' => (bool) $row->active,
                ];
            });

        return compact('items');

    }

    public function searchCustomers(Request $request)
    {

        // $identity_document_type_id = $this->getIdentityDocumentTypeId($request->document_type_id, $request->operation_type_id);

        $customers = Person::where('number','like', "%{$request->input}%")
                            ->orWhere('name','like', "%{$request->input}%")
                            ->whereType('customers')->orderBy('name')
                            // ->whereIn('identity_document_type_id',$identity_document_type_id)
                            ->whereIsEnabled()
                            ->get()->transform(function($row) {
                                return [
                                    'id' => $row->id,
                                    'description' => $row->number.' - '.$row->name,
                                    'name' => $row->name,
                                    'number' => $row->number,
                                    'identity_document_type_id' => $row->identity_document_type_id,
                                    'address' =>  $row->address,
                                    'email' =>  $row->email,
                                    'telephone' =>  $row->telephone,
                                    'type_person_id' => $row->type_person_id,
                                    'type_regime_id' => $row->type_regime_id,
                                    'city_id' => $row->city_id,
                                    'type_obligation_id' => $row->type_obligation_id,
                                    'dv' => $row->dv
                                ];
                            });

        return compact('customers');
    }


    /**
     *
     * Descargar xml - pdf
     * Usado en:
     * DocumentController - comprobantes
     * DocumentPayrollController - nóminas
     *
     * @param string $filename
     * @return array
     */
    public function downloadFile($filename)
    {
        $company = ServiceTenantCompany::firstOrFail();
        $base_url = config('tenant.service_fact');
        $ch2 = curl_init("{$base_url}ubl2.1/download/{$company->identification_number}/{$filename}/BASE64");

        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch2, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
            "Authorization: Bearer {$company->api_token}"
        ));
        $response_data = curl_exec($ch2);
        $err = curl_error($ch2);
        curl_close($ch2);
        if($err){
            return [
                'success' => false,
                'message' => "No se pudo descargar el archivo: ".$filename
            ];
        }
        else{
            return $response_data;
        }
    }

    public function downloadFileCoupon($id)
    {
        $purchaseCoupon = CustomerPurchaseCoupon::where('document_id',$id)->where('status',1)->first();
        if (!$purchaseCoupon) {
            return response()->json([
                'success' => false,
                'message' => 'Cupón no encontrado'
            ], 404);
        }

        $coupon = ConfigurationPurchaseCoupon::where('id',$purchaseCoupon->configuration_purchase_coupon_id)->where('status',1)->first();

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Cupón no encontrado'
            ], 404);
        }

        $data = [
            'title' => $coupon->title,
            'description' => $coupon->description,
            'establishment' => $coupon->establishment,
            'coupon_date' => $coupon->coupon_date,
            'document_number' => $purchaseCoupon->document_number,
            'customer_name' => $purchaseCoupon->customer_name,
            'customer_number' => $purchaseCoupon->customer_number,
            'customer_phone' => $purchaseCoupon->customer_phone,
            'customer_email' => $purchaseCoupon->customer_email,
            'document_amount' => $purchaseCoupon->document_amount,
        ];

        $html = View::make('factcolombia1::coupon.coupon', $data)->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => [72, 297], // 72mm de ancho (tirilla), altura variable
            'margin_left' => 5,
            'margin_right' => 5,
            'margin_top' => 5,
            'margin_bottom' => 5,
            'default_font' => 'symbola'
        ]);

        $mpdf->WriteHTML($html);

        $pdfContent = $mpdf->Output('', 'S'); // 'S' devuelve el string del contenido

        return response()->json([
            'success' => true,
            'filebase64' => base64_encode($pdfContent),
            'filename' => 'cupon-'.$purchaseCoupon->document_number.'.pdf'
        ]);

    }

    public function searchCustomerById($id)
    {

        $customers = Person::whereType('customers')
                    ->where('id',$id)
                    ->get()->transform(function($row) {
                        return [
                            'id' => $row->id,
                            'description' => $row->number.' - '.$row->name,
                            'name' => $row->name,
                            'number' => $row->number,
                            'identity_document_type_id' => $row->identity_document_type_id,
                            'address' =>  $row->address,
                            'email' =>  $row->email,
                            'telephone' =>  $row->telephone,
                            'type_person_id' => $row->type_person_id,
                            'type_regime_id' => $row->type_regime_id,
                            'city_id' => $row->city_id,
                            'type_obligation_id' => $row->type_obligation_id,
                            'dv' => $row->dv
                        ];
            });

        return compact('customers');
    }


    public function searchItemById($id)
    {

        $establishment_id = auth()->user()->establishment_id;
        $warehouse = ModuleWarehouse::where('establishment_id', $establishment_id)->first();

        $search_item = $this->getItemsNotServicesById($id);

        if(count($search_item) == 0){
            $search_item = $this->getItemsServicesById($id);
        }

        $items = collect($search_item)->transform(function($row) use($warehouse){

            $detail = $this->getFullDescription($row, $warehouse);

            return [
                'id' => $row->id,
                'name' => $row->name,
                'full_description' => $detail['full_description'],
                'brand' => $detail['brand'],
                'category' => $detail['category'],
                'stock' => $detail['stock'],
                'internal_id' => $row->internal_id,
                'description' => $row->description,
                'currency_type_id' => $row->currency_type_id,
                'currency_type_symbol' => $row->currency_type->symbol,
                'sale_unit_price' => round($row->sale_unit_price, 2),
                'purchase_unit_price' => $row->purchase_unit_price,
                'unit_type_id' => $row->unit_type_id,
                'sale_affectation_igv_type_id' => $row->sale_affectation_igv_type_id,
                'purchase_affectation_igv_type_id' => $row->purchase_affectation_igv_type_id,
                'calculate_quantity' => (bool) $row->calculate_quantity,
                'has_igv' => (bool) $row->has_igv,
                'amount_plastic_bag_taxes' => $row->amount_plastic_bag_taxes,
                'item_unit_types' => collect($row->item_unit_types)->transform(function($row) {
                    return [
                        'id' => $row->id,
                        'description' => "{$row->description}",
                        'item_id' => $row->item_id,
                        'unit_type_id' => $row->unit_type_id,
                        'unit_type' => $row->unit_type,
                        'quantity_unit' => $row->quantity_unit,
                        'price1' => $row->price1,
                        'price2' => $row->price2,
                        'price3' => $row->price3,
                        'price_default' => $row->price_default,
                    ];
                }),
                'warehouses' => collect($row->warehouses)->transform(function($row) use($warehouse){
                    return [
                        'warehouse_description' => $row->warehouse->description,
                        'stock' => $row->stock,
                        'warehouse_id' => $row->warehouse_id,
                        'checked' => ($row->warehouse_id == $warehouse->id) ? true : false,
                    ];
                }),
                'attributes' => $row->attributes ? $row->attributes : [],
                'lots_group' => collect($row->lots_group)->transform(function($row){
                    return [
                        'id'  => $row->id,
                        'code' => $row->code,
                        'quantity' => $row->quantity,
                        'date_of_due' => $row->date_of_due,
                        'checked'  => false
                    ];
                }),
                'lots' => $row->item_lots->where('has_sale', false)->where('warehouse_id', $warehouse->id)->transform(function($row) {
                    return [
                        'id' => $row->id,
                        'series' => $row->series,
                        'date' => $row->date,
                        'item_id' => $row->item_id,
                        'warehouse_id' => $row->warehouse_id,
                        'has_sale' => (bool)$row->has_sale,
                        'lot_code' => ($row->item_loteable_type) ? (isset($row->item_loteable->lot_code) ? $row->item_loteable->lot_code:null):null
                    ];
                }),
                'lots_enabled' => (bool) $row->lots_enabled,
                'series_enabled' => (bool) $row->series_enabled,
                'unit_type' => $row->unit_type,
                'tax' => $row->tax,
                'active' => (bool) $row->active,

            ];
        });

        return compact('items');
    }

    public function searchExternalId($external_id)
    {
        return response()->json(Document::where('external_id', $external_id)->first());
    }


    public function store_aiu(DocumentRequest $request) {
        DB::connection('tenant')->beginTransaction();

        try {
            if(!$request->customer_id)
            {
                $customer = (object)$request->service_invoice['customer'];

                $person = Person::updateOrCreate([
                    'type' => 'customers',
                    'identity_document_type_id' => $customer->identity_document_type_id,
                    'number' => $customer->identification_number,
                ], [
                    'code' => random_int(1000, 9999),
                    'name' => $customer->name,
                    'country_id' => 47,
                    'department_id' => 779,
                    'city_id' => 12688,
                    'address' => $customer->address,
                    'email' => $customer->email,
                    'telephone' => $customer->phone,
                ]);

                $request['customer_id'] = $person->id;
            }

            $response =  null;
            $response_status =  null;

            $company = ServiceTenantCompany::firstOrFail();

            //si la empresa esta en habilitacion, envio el parametro ignore_state_document_id en true
            //  para buscar el correlativo en api sin filtrar por el campo state_document_id=1
            $ignore_state_document_id = ($company->type_environment_id === 2);
            $correlative_api = $this->getCorrelativeInvoice(1, $request->prefix, $ignore_state_document_id);
            // $correlative_api = $this->getCorrelativeInvoice(1);

            // dd($correlative_api);

            if(!is_numeric($correlative_api)){
                return [
                    'success' => false,
                    'message' => 'Error al obtener correlativo Api.'
                ];
            }

            $service_invoice = $request->service_invoice;
            $service_invoice['number'] = $correlative_api;
            $service_invoice['prefix'] = $request->prefix;
            $service_invoice['resolution_number'] = $request->resolution_number;
            if ($request->filled('head_note')) {
                $service_invoice['head_note'] = $request->head_note;
            }
            if ($request->filled('foot_note')) {
                $service_invoice['foot_note'] = $request->foot_note;
            }
            // if ($request->filled('notes')) {
            //     $service_invoice['notes'] = $request->notes;
            // }
            if ($request->order_reference)
            {
                if (isset($request['order_reference']['issue_date_order']) && isset($request['order_reference']['id_order']))
                {
                    $service_invoice['order_reference']['id_order'] = $request['order_reference']['id_order'];
                    $service_invoice['order_reference']['issue_date_order'] = $request['order_reference']['issue_date_order'];
                }
            }

            $datoscompany = Company::with('type_regime', 'type_identity_document')->firstOrFail();
            // $company = ServiceTenantCompany::firstOrFail();

            if(file_exists(storage_path('sendmail.api')))
                $service_invoice['sendmail'] = true;
            $service_invoice['ivaresponsable'] = $datoscompany->type_regime->name;
            $service_invoice['nombretipodocid'] = $datoscompany->type_identity_document->name;
            $service_invoice['tarifaica'] = $datoscompany->ica_rate;
            $service_invoice['actividadeconomica'] = $datoscompany->economic_activity_code;
            $service_invoice['notes'] = $request->observation;
            $service_invoice['date'] = date('Y-m-d');
            $service_invoice['time'] = date('H:i:s');
            $service_invoice['payment_form']['payment_form_id'] = $request->payment_form_id;
            $service_invoice['payment_form']['payment_method_id'] = $request->payment_method_id;
            $service_invoice['is_tirilla2'] = $request->input('is_tirilla2', false);
            if($request->payment_form_id == '1')
                $service_invoice['payment_form']['payment_due_date'] = date('Y-m-d');
            else
                $service_invoice['payment_form']['payment_due_date'] = date('Y-m-d', strtotime($request->date_expiration));
            $service_invoice['payment_form']['duration_measure'] = $request->time_days_credit;

            $id_test = $company->test_id;
            $base_url = config('tenant.service_fact');

            if($company->type_environment_id == 2 && $company->test_id != 'no_test_set_id')
                $ch = curl_init("{$base_url}ubl2.1/invoice-aiu/{$id_test}");
            else
                $ch = curl_init("{$base_url}ubl2.1/invoice-aiu");

            $data_document = json_encode($service_invoice);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS,($data_document));
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Accept: application/json',
                "Authorization: Bearer {$company->api_token}"
            ));
            $response = curl_exec($ch);
            curl_close($ch);

            $response_model = json_decode($response);
            $zip_key = null;
            $invoice_status_api = null;
            $response_status = null;

//            \Log::debug(json_encode($response_model));
            //return json_encode($response_model);

            if($company->type_environment_id == 2 && $company->test_id != 'no_test_set_id'){
                if(array_key_exists('urlinvoicepdf', $response_model) && array_key_exists('urlinvoicexml', $response_model))
                {
                    if(!is_string($response_model->ResponseDian->Envelope->Body->SendTestSetAsyncResponse->SendTestSetAsyncResult->ZipKey))
                    {
                        if(is_string($response_model->ResponseDian->Envelope->Body->SendTestSetAsyncResponse->SendTestSetAsyncResult->ErrorMessageList->XmlParamsResponseTrackId->Success))
                        {
                            if($response_model->ResponseDian->Envelope->Body->SendTestSetAsyncResponse->SendTestSetAsyncResult->ErrorMessageList->XmlParamsResponseTrackId->Success == 'false')
                            {
                                return [
                                    'success' => false,
                                    'message' => $response_model->ResponseDian->Envelope->Body->SendTestSetAsyncResponse->SendTestSetAsyncResult->ErrorMessageList->XmlParamsResponseTrackId->ProcessedMessage
                                ];
                            }
                        }
                    }
                    else
                        if(is_string($response_model->ResponseDian->Envelope->Body->SendTestSetAsyncResponse->SendTestSetAsyncResult->ZipKey))
                        {
                            $zip_key = $response_model->ResponseDian->Envelope->Body->SendTestSetAsyncResponse->SendTestSetAsyncResult->ZipKey;
                        }
                }


                //declaro variuable response status en null

                //compruebo zip_key para ejecutar servicio de status document

                // ************* queryZipkey ************
                // if($zip_key)
                // {
                //     //espero 3 segundos para ejecutar sevcio de status document
                //     sleep(3);

                //     $ch2 = curl_init("{$base_url}ubl2.1/status/zip/{$zip_key}");
                //     curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                //     curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "POST");

                //     if(file_exists(storage_path('sendmail.api'))){
                //         curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode(array("sendmail" => true)));
                //     }
                //     curl_setopt($ch2, CURLOPT_HTTPHEADER, array(
                //         'Content-Type: application/json',
                //         'Accept: application/json',
                //         "Authorization: Bearer {$company->api_token}"
                //     ));
                //     $response_status = curl_exec($ch2);
                //     curl_close($ch2);
                //     $response_status_decoded = json_decode($response_status);

                //    // return json_encode($response_status_decoded);


                //     if(property_exists($response_status_decoded, 'ResponseDian')){
                //         if($response_status_decoded->ResponseDian->Envelope->Body->GetStatusZipResponse->GetStatusZipResult->DianResponse->IsValid == "true")
                //             $this->setStateDocument(1, $correlative_api);
                //         else
                //         {
                //             if(is_array($response_status_decoded->ResponseDian->Envelope->Body->GetStatusZipResponse->GetStatusZipResult->DianResponse->ErrorMessage->string))
                //                 $mensajeerror = implode(",", $response_status_decoded->ResponseDian->Envelope->Body->GetStatusZipResponse->GetStatusZipResult->DianResponse->ErrorMessage->string);
                //             else
                //                 $mensajeerror = $response_status_decoded->ResponseDian->Envelope->Body->GetStatusZipResponse->GetStatusZipResult->DianResponse->ErrorMessage->string;
                //             if($response_status_decoded->ResponseDian->Envelope->Body->GetStatusZipResponse->GetStatusZipResult->DianResponse->IsValid == 'false')
                //             {
                //                 return [
                //                     'success' => false,
                //                     'message' => "Error al Validar Factura Nro: {$correlative_api} Errores: ".$mensajeerror
                //                 ];
                //             }
                //         }
                //     }
                //     else{
                //         $mensajeerror = $response_status_decoded->message;
                //         return [
                //             'success' => false,
                //             'message' => "Error al Validar Factura Nro: {$correlative_api} Errores: ".$mensajeerror
                //         ];

                //     }
                // }
                // else
                //     return [
                //         'success' => false,
                //         'message' => "Error de ZipKey."
                //     ];
                // ************* queryZipkey ************

            }
            else{
                if($response_model->ResponseDian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult->IsValid == "true")
                    $this->setStateDocument(1, $correlative_api);
                else
                {
                    if(is_array($response_model->ResponseDian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult->ErrorMessage->string))
                        $mensajeerror = implode(",", $response_model->ResponseDian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult->ErrorMessage->string);
                    else
                        $mensajeerror = $response_model->ResponseDian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult->ErrorMessage->string;
                    if($response_model->ResponseDian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult->IsValid == 'false')
                    {
                        return [
                            'success' => false,
                            'message' => "Error al Validar Factura Nro: {$correlative_api} Errores: ".$mensajeerror
                        ];
                    }
                }
            }

            $nextConsecutive = FacadeDocument::nextConsecutive($request->type_document_id);
            $this->company = Company::query()
                ->with('country', 'version_ubl', 'type_identity_document')
                ->firstOrFail();

            // if (($this->company->limit_documents != 0) && (Document::count() >= $this->company->limit_documents)) throw new \Exception("Has excedido el límite de documentos de tu cuenta.");
            if($response_model->ResponseDian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult->IsValid == 'true') {
                $state_document_id = self::ACCEPTED;
            } else {
                $state_document_id = self::REJECTED;
            }

            $request->merge(['state_document_id' => $state_document_id]);
            $this->document = DocumentHelper::createDocument($request, $nextConsecutive, $correlative_api, $this->company, $response, $response_status, $company->type_environment_id);
            (new DocumentHelper())->savePayments($this->document, $request->payments,$request);


        }
        catch (\Exception $e) {
            DB::connection('tenant')->rollBack();

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
            ];
        }

        DB::connection('tenant')->commit();

        return [
            'success' => true,
            'message' => "Se registro con éxito el documento #{$this->document->prefix}",
            'data' => [
                'id' => $this->document->id
            ]
           //'data' => $data_document
        ];
    }

    private function registerCustomerCoupon($document) {

        $activeCoupon = ConfigurationPurchaseCoupon::where('status',1)->first();
        $customer = Person::where('id',$document->customer_id)->first();

        if($activeCoupon && $customer && $document->total >= $activeCoupon->minimum_purchase_amount){
            CustomerPurchaseCoupon::create([
                'configuration_purchase_coupon_id'  => $activeCoupon->id,
                'document_id'  => $document->id,
                'document_number'  => $document->prefix.'-'.$document->number,
                'customer_name'  => $customer->name,
                'customer_number'  => $customer->number,
                'customer_phone'  => $customer->telephone,
                'customer_email'  => $customer->email,
                'document_amount'  => $document->total,
                'status'  => 1
            ]);
        }
    }

}
