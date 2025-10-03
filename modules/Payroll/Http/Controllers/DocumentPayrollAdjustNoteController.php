<?php

namespace Modules\Payroll\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Payroll\Models\{
    DocumentPayrollAdjustNote,
    DocumentPayroll,
    Worker
};
use Modules\Payroll\Http\Resources\{
    DocumentPayrollAdjustNoteResource
};
use Modules\Payroll\Http\Requests\DocumentPayrollAdjustNoteRequest;
use Modules\Factcolombia1\Models\TenantService\{
    PayrollPeriod,
    TypeLawDeductions,
    TypeDisability,
    AdvancedConfiguration,
    TypeOvertimeSurcharge,
};
use Modules\Factcolombia1\Models\Tenant\{
    PaymentMethod,
    TypeDocument,
};
use Illuminate\Support\Facades\DB;
use Exception;
use Modules\Payroll\Helpers\DocumentPayrollHelper;
use Modules\Factcolombia1\Http\Controllers\Tenant\DocumentController;
use Modules\Payroll\Traits\UtilityTrait; 


class DocumentPayrollAdjustNoteController extends Controller
{
    
    use UtilityTrait;

    public function create($id)
    {
        $type_payroll_adjust_note_id = DocumentPayrollAdjustNote::ADJUST_NOTE_REPLACE_ID;

        return view('payroll::document-payrolls.form', compact('id', 'type_payroll_adjust_note_id'));
    }

 
    public function tables($type_payroll_adjust_note_id)
    {
        $establishment_id = auth()->user()->establishment_id;

        $resolutions = TypeDocument::select('id', 'prefix', 'resolution_number', 'show_in_establishments', 'establishment_ids')
            ->where('code', DocumentPayroll::ADJUST_NOTE_TYPE_DOCUMENT_ID)
            ->where(function($query) use ($establishment_id) {
                $query->where('show_in_establishments', 'all')
                    ->orWhere(function($q) use ($establishment_id) {
                        $q->where('show_in_establishments', 'custom')
                            ->whereJsonContains('establishment_ids', $establishment_id);
                    });
            })
            ->get();

        // nomina eliminacion
        if($type_payroll_adjust_note_id == DocumentPayrollAdjustNote::ADJUST_NOTE_ELIMINATION_ID)
        {
            return [
                'resolutions' => $resolutions
            ];
        }

        // nomina de reemplazo
        
        return [
            'workers' => [],
            'payroll_periods' => PayrollPeriod::get(),
            'type_disabilities' => TypeDisability::get(),
            'payment_methods' => PaymentMethod::get(),
            'type_law_deductions' => TypeLawDeductions::whereTypeLawDeductionsWorker()->get(),
            'advanced_configuration' => AdvancedConfiguration::first(),
            'resolutions' => $resolutions
        ];

    }
        
    /**
     * Buscar nómina afectada
     *
     * @param  int $id
     * @return DocumentPayrollAdjustNoteResource
     */
    public function record($id)
    {
        $document = DocumentPayroll::with([
            'accrued', 
            'deduction',
            'worker',
            'payroll_period'
        ])->findOrFail($id);

        return new DocumentPayrollAdjustNoteResource($document);
    }
    
     
    /**
     * 
     * Registar nómina de eliminación/reemplazo
     *
     * @param  DocumentPayrollAdjustNoteRequest $request
     * @return array
     */
    public function store(DocumentPayrollAdjustNoteRequest $request)
    {
        try {
            // dd($request->all());
            DB::connection('tenant')->beginTransaction();
            
            // Obtener y validar inputs
            $helper = new DocumentPayrollHelper();
            $inputs = $helper->getInputsAdjustNote($request);
            
            // Procesar los datos de accrued antes de guardar
            if (isset($inputs['accrued'])) {
                $inputs['accrued'] = $this->processAccruedData($inputs['accrued']);
            }
            
            // Crear documento base
            $document = DocumentPayroll::create($inputs);
            
            // Crear nota de ajuste
            $document->adjust_note()->create($inputs['adjust_note']);

            // Si es nómina de reemplazo
            if(!$document->adjust_note->is_adjust_note_elimination) {
                $document->accrued()->create($inputs['accrued']);
                $document->deduction()->create($inputs['deduction']);
            }

            // Enviar a API
            $send_to_api = $helper->sendToApi($document, $inputs);
            
            // Actualizar respuesta
            $document->update([
                'response_api' => $send_to_api
            ]);

            DB::connection('tenant')->commit();
            
            return [
                'success' => true,
                'message' => $document->adjust_note->is_adjust_note_elimination ? 
                            "Nómina de eliminación {$document->number_full} registrada con éxito" : 
                            "Nómina de reemplazo {$document->number_full} registrada con éxito",
                'data' => [
                    'id' => $document->id
                ]
            ];

        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            \Log::error($e->getMessage());
            return $this->getErrorFromException($e->getMessage(), $e);
        }
    }

    protected function processAccruedData($accrued)
    {
        $total_accrued = floatval($accrued['total_base_salary'] ?? 0);

        $direct_fields = [
            'transportation_allowance',
            'total_extra_hours',
            'total_license',
            'endowment',
            'sustenance_support',
            'telecommuting',
            'withdrawal_bonus',
            'compensation',
            'salary_viatics',
            'non_salary_viatics',
            'refund'
        ];

        foreach ($direct_fields as $field) {
            if (isset($accrued[$field]) && $accrued[$field] !== null && $accrued[$field] !== '') {
                $total_accrued += floatval($accrued[$field]);
            }
        }

        $array_fields = [
            'heds' => ['payment'],
            'hens' => ['payment'],
            'hrns' => ['payment'],
            'heddfs' => ['payment'],
            'hrddfs' => ['payment'],
            'hendfs' => ['payment'],
            'hrndfs' => ['payment'],
            'work_disabilities' => ['payment'],
            'service_bonus' => ['payment', 'paymentNS'],
            'severance' => ['payment', 'interest_payment'],
            'common_vacation' => ['payment'],
            'paid_vacation' => ['payment'],
            'bonuses' => ['salary_bonus', 'non_salary_bonus'],
            'aid' => ['salary_assistance', 'non_salary_assistance'],
            'other_concepts' => ['salary_concept', 'non_salary_concept'],
            'commissions' => ['commission'],
            'epctv_bonuses' => ['paymentS', 'paymentNS', 'salary_food_payment', 'non_salary_food_payment'],
            'third_party_payments' => ['third_party_payment'],
            'advances' => ['advance'],
            'compensations' => ['ordinary_compensation', 'extraordinary_compensation'],
            'legal_strike' => ['payment'],
        ];

        foreach ($array_fields as $field => $value_keys) {
            if (isset($accrued[$field]) && is_array($accrued[$field])) {
                foreach ($accrued[$field] as $item) {
                    foreach ($value_keys as $value_key) {
                        if (isset($item[$value_key]) && $item[$value_key] !== null && $item[$value_key] !== '') {
                            $total_accrued += floatval($item[$value_key]);
                        }
                    }
                }
            }
        }

        $accrued['accrued_total'] = round($total_accrued, 2);

        return $accrued;
    }
        
}
