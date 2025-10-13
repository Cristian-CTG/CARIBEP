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
            ->where(function($query) {
                $query->whereNull('resolution_date_end')
                    ->orWhere('resolution_date_end', '>=', now()->format('Y-m-d'));
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


}
