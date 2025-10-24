<?php

namespace Modules\Purchase\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Person;
use App\Models\Tenant\Establishment;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant\Company;
use Illuminate\Support\Str;
use App\CoreFacturalo\Requests\Inputs\Common\PersonInput;
use Carbon\Carbon;
use Modules\Purchase\Models\{
    SupportDocument   
};
use Modules\Purchase\Http\Resources\{
    SupportDocumentCollection,
    SupportDocumentResource
};
use Modules\Factcolombia1\Models\Tenant\{
    TypeDocument,
    Currency,
    PaymentMethod,
    PaymentForm
};
use Modules\Factcolombia1\Models\TenantService\{
    TypeGenerationTransmition
};
use Modules\Purchase\Http\Requests\SupportDocumentRequest;
use Modules\Purchase\Helpers\SupportDocumentHelper;
use Modules\Factcolombia1\Http\Controllers\Tenant\DocumentController;
use Modules\Payroll\Traits\UtilityTrait; 
use Modules\Accounting\Models\ChartOfAccount;
use Modules\Accounting\Models\AccountingChartAccountConfiguration;
use Modules\Accounting\Helpers\AccountingEntryHelper;
use Modules\Accounting\Models\ThirdParty;
use App\Models\Tenant\Catalogs\DocumentType;
use Modules\Factcolombia1\Models\Tenant\TypeIdentityDocument;


class SupportDocumentController extends Controller
{

    use UtilityTrait;

    public function index()
    {
        return view('purchase::support_documents.index');
    }

    public function create()
    {
        return view('purchase::support_documents.form');
    }
    
    /**
     * 
     * Campos para filtros
     *
     * @return array
     */
    public function columns()
    {
        return [
            'number' => 'Número',
            'date_of_issue' => 'Fecha de emisión',
        ];
    }
    
    
    /**
     * Listado
     *
     * @param  mixed $request
     * @return SupportDocumentCollection
     */
    public function records(Request $request)
    {
        if ($request->column == 'date_of_issue') {
            if (strlen($request->value) == 7) {
                // Si el valor es un mes (YYYY-MM), filtrar por todo el mes
                $year_month = explode('-', $request->value);
                $year = $year_month[0];
                $month = $year_month[1];
                
                $records = SupportDocument::with(['type_document'])
                            ->whereYear('date_of_issue', $year)
                            ->whereMonth('date_of_issue', $month);
            } else {
                // Si es una fecha específica (YYYY-MM-DD)
                $records = SupportDocument::with(['type_document'])
                            ->whereDate('date_of_issue', $request->value);
            }
        } else {
            $records = SupportDocument::with(['type_document'])
                        ->where($request->column, 'like', "%{$request->value}%");
        }

        return new SupportDocumentCollection($records->latest()->paginate(config('tenant.items_per_page')));
    }

    
    /**
     *
     * @return array
     */
    public function tables()
    {
        $suppliers = $this->generalTable('suppliers');
        
        $establishment_id = auth()->user()->establishment_id;
        
        $resolutions = TypeDocument::select('id','prefix', 'resolution_number', 'code', 'show_in_establishments', 'establishment_ids')
            ->where('code', TypeDocument::DSNOF_CODE)
            ->where(function($query) use ($establishment_id) {
                $query->where('show_in_establishments', 'all')
                    ->orWhere(function($subQuery) use ($establishment_id) {
                        $subQuery->where('show_in_establishments', 'custom');
                        
                        if ($establishment_id) {
                            $subQuery->whereJsonContains('establishment_ids', $establishment_id);
                        }
                    });
            })
            ->get();
            
        $payment_methods = PaymentMethod::get();
        $payment_forms = PaymentForm::get();
        $currencies = Currency::get();
        $taxes = $this->generalTable('taxes');

        return compact('suppliers','payment_methods','payment_forms','currencies', 'taxes', 'resolutions');
    }

    
    /**
     *
     * @return array
     */
    public function item_tables()
    {
        $items = $this->generalTable('items');
        $taxes = $this->generalTable('taxes');
        $type_generation_transmitions = TypeGenerationTransmition::get();

        return compact('items', 'taxes', 'type_generation_transmitions');
    }

        
    /**
     * Descarga de xml/pdf
     *
     * @param string $filename
     */
    public function downloadFile($filename)
    {
        return app(DocumentController::class)->downloadFile($filename);
    }

    
    /**
     * @param  int $id
     * @return SupportDocumentResource
     */
    public function record($id)
    {
        return new SupportDocumentResource(SupportDocument::findOrFail($id));
    }

    
    /**
     * 
     * Registrar documento de soporte
     *
     * @param  SupportDocumentRequest $request
     * @return array
     */
    public function store(SupportDocumentRequest $request)
    {
        //dd($request->all());
        try 
        {
            $support_document = DB::connection('tenant')->transaction(function () use ($request) {

                $helper = new SupportDocumentHelper();
                $inputs = $helper->getInputs($request);

                $document =  SupportDocument::create($inputs);
                
                foreach ($inputs['items'] as $row)
                {
                    $document->items()->create($row); 
                }

                // Guardar pagos automáticamente si es al contado
                $helper->savePayments($document, [], $request);

                // <<<--- AGREGAR ESTA LÍNEA
                $this->registerAccountingSupportDocumentEntries($document);

                // enviar documento a la api
                $send_to_api = $helper->sendToApi($document, $inputs);                

                $document->update([
                    'response_api' => $send_to_api
                ]);

                return $document;

            });

            return [
                'success' => true,
                'message' => 'Documento de soporte registrado con éxito',
                'data' => [
                    'id' => $support_document->id,
                    'number_full' => $support_document->number_full,
                ],
            ];
            
        } catch (Exception $e)
        {
            return $this->getErrorFromException($e->getMessage(), $e);
        }

    }

    private function registerAccountingSupportDocumentEntries($document)
    {
        $accountConfiguration = AccountingChartAccountConfiguration::first();
        if(!$accountConfiguration) return;
        $accountIdInventory = ChartOfAccount::where('code', $accountConfiguration->inventory_account)->first();
        $accountIdLiability = ChartOfAccount::where('code', $accountConfiguration->supplier_payable_account)->first();
        // Definir descripción según el tipo de documento soporte
        $description = 'Documento de Soporte';
        if (
            (isset($document->type_document) && $document->type_document->code == '13')
        ) {
            $description = 'Nota de Ajuste de Documento de Soporte';
        }

        // Obtener proveedor como tercer implicado
        $supplier = Person::find($document->supplier_id);
        $thirdPartyId = null;
        $documentType = null;
        if ($supplier->identity_document_type_id) {
            $typeDoc = TypeIdentityDocument::find($supplier->identity_document_type_id);
            $documentType = $typeDoc ? $typeDoc->code : null;
        }
        if ($supplier) {
            $thirdParty = ThirdParty::updateOrCreate(
                ['document' => $supplier->number, 'type' => $supplier->type],
                ['name' => $supplier->name, 'email' => $supplier->email, 'address' => $supplier->address, 'phone' => $supplier->telephone, 'document_type' => $documentType]
            );
            $thirdPartyId = $thirdParty->id;
        }

        // Agrupar subtotales por cuenta contable
        $accounts = [];
        foreach ($document->items as $item) {
            $account = null;
            if (!empty($item->chart_of_account_code)) {
                $account = ChartOfAccount::where('code', $item->chart_of_account_code)->first();
            }
            if (!$account) {
                $account = $accountIdInventory;
            }
            if (!$account) continue;

            // Sumar solo el valor neto (sin impuestos)
            $valor_neto = floatval($item->unit_price) * floatval($item->quantity) - floatval($item->discount ?? 0);
            if (!isset($accounts[$account->id])) {
                $accounts[$account->id] = [
                    'account' => $account,
                    'subtotal' => 0,
                ];
            }
            $accounts[$account->id]['subtotal'] += $valor_neto;
        }

        // Construir movimientos para el asiento contable
        $movements = [];
        foreach ($accounts as $acc) {
            $movements[] = [
                'account_id' => $acc['account']->id,
                'debit' => $acc['subtotal'],
                'credit' => 0,
                'affects_balance' => true,
                'third_party_id' => $thirdPartyId,
                'description' => $acc['account']->code . ' - ' . $acc['account']->name,
            ];
        }

        if ($document->payment_form_id == 1) {
            // Contado: usar caja general (110505) o bancos (111005) según el medio de pago
            // Puedes mejorar esto para usar bancos si corresponde
            $accountCash = ChartOfAccount::where('code', '110505')->first();
            $movements[] = [
                'account_id' => $accountCash->id,
                'debit' => 0,
                'credit' => $document->total,
                'affects_balance' => true,
                'third_party_id' => $thirdPartyId,
                'description' => $accountCash->code . ' - ' . $accountCash->name,
            ];
        } else {
            // Crédito: proveedores
            $movements[] = [
                'account_id' => $accountIdLiability->id,
                'debit' => 0,
                'credit' => $document->total,
                'affects_balance' => true,
                'third_party_id' => $thirdPartyId,
                'description' => $accountIdLiability->code . ' - ' . $accountIdLiability->name,
            ];
        }

        AccountingEntryHelper::registerEntry([
            'prefix_id' => 2,
            'description' => $description . ' #' . $document->prefix . '-' . $document->number,
            'support_document_id' => $document->id,
            'movements' => $movements,
            'taxes' => is_array($document->taxes) ? $document->taxes : (is_object($document->taxes) ? (array)$document->taxes : []),
            'tax_config' => [
                'tax_field' => 'chart_account_purchase',
                'tax_debit' => true,
                'tax_credit' => false,
                'retention_debit' => false,
                'retention_credit' => true,
                'third_party_id' => $thirdPartyId,
            ],
        ]);
    }


}
