<?php

namespace Modules\Purchase\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant\Person;
use Carbon\Carbon;
use Modules\Purchase\Models\{
    SupportDocument  
};
use Modules\Factcolombia1\Models\Tenant\{
    TypeDocument,
    NoteConcept,
};
use Modules\Purchase\Http\Requests\SupportDocumentAdjustNoteRequest;
use Modules\Purchase\Helpers\SupportDocumentHelper;
use Modules\Payroll\Traits\UtilityTrait;
use Modules\Accounting\Models\ChartOfAccount;
use Modules\Accounting\Models\AccountingChartAccountConfiguration;
use Modules\Accounting\Helpers\AccountingEntryHelper;
use Modules\Accounting\Models\ThirdParty;
use App\Models\Tenant\Catalogs\DocumentType;
use Modules\Factcolombia1\Models\Tenant\TypeIdentityDocument;


class SupportDocumentAdjustNoteController extends Controller
{

    use UtilityTrait;

    public function create($support_document_id)
    {
        return view('purchase::support_documents.form_adjust_note', compact('support_document_id'));
    }
    
    
    /**
     *
     * @return array
     */
    public function tables()
    {
        $resolutions = TypeDocument::getResolutionsByCode(TypeDocument::DSNOF_ADJUST_NOTE_CODE);
        $currencies = $this->generalTable('currencies');
        $taxes = $this->generalTable('taxes');
        $credit_note_concepts = NoteConcept::where('type_document_id', 3)->get(); //notas credito

        return compact('currencies', 'taxes', 'resolutions', 'credit_note_concepts');
    }

     
    /**
     * @param  int $id
     * @return array
     */
    public function record($id)
    {
        return SupportDocument::getDataAdjustNote($id);
    }

    
    /**
     * 
     * Registrar nota de ajuste del documento de soporte
     *
     * @param  SupportDocumentAdjustNoteRequest $request
     * @return array
     */
    public function store(SupportDocumentAdjustNoteRequest $request)
    {
        try 
        {
            $support_document = DB::connection('tenant')->transaction(function () use ($request) {

                $helper = new SupportDocumentHelper();
                $inputs = $helper->getInputs($request);

                $document =  SupportDocument::create($inputs);

                $document->support_document_adjust_note()->create($inputs['adjust_note']);
                
                foreach ($inputs['items'] as $row)
                {
                    $document->items()->create($row); 
                }

                // <<<--- AGREGAR ESTA LÍNEA
                $this->registerAccountingSupportDocumentAdjustNoteEntries($document);
                // enviar documento a la api
                $send_to_api = $helper->sendToApi($document, $inputs);
                

                $document->update([
                    'response_api' => $send_to_api
                ]);

                return $document;

            });

            return [
                'success' => true,
                'message' => 'La nota de ajuste del documento de soporte fue registrada con éxito',
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

    private function registerAccountingSupportDocumentAdjustNoteEntries($document)
    {
        $accountConfiguration = AccountingChartAccountConfiguration::first();
        if(!$accountConfiguration) return;
        $accountIdInventory = ChartOfAccount::where('code', $accountConfiguration->inventory_account)->first();
        $accountIdLiability = ChartOfAccount::where('code', $accountConfiguration->supplier_payable_account)->first();

        // Descripción para la nota de ajuste
        $description = 'Nota de Ajuste de Documento de Soporte';

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

        AccountingEntryHelper::registerEntry([
            'prefix_id' => 2,
            'description' => $description . ' #' . $document->prefix . '-' . $document->number,
            'support_document_id' => $document->id,
            'movements' => [
                [
                    'account_id' => $accountIdInventory->id,
                    'debit' => $document->sale,
                    'credit' => 0,
                    'affects_balance' => true,
                    'third_party_id' => $thirdPartyId,
                ],
                [
                    'account_id' => $accountIdLiability->id,
                    'debit' => 0,
                    'credit' => $document->total,
                    'affects_balance' => true,
                    'third_party_id' => $thirdPartyId,
                ],
            ],
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
