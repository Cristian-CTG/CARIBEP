<?php

namespace Modules\RadianEvent\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\Factcolombia1\Helpers\HttpConnectionApi;
use Modules\Factcolombia1\Models\TenantService\AdvancedConfiguration;
use Modules\Factcolombia1\Models\TenantService\{
    Company as ServiceCompany
};
use Exception;
use Modules\RadianEvent\Models\{
    ReceivedDocument
};
use Modules\RadianEvent\Http\Resources\{
    ReceivedDocumentCollection
};
use SimpleXMLElement;

class RadianEventController extends Controller
{
    
    public function reception()
    {
        return view('radianevent::reception.index');
    }

       
    public function manage()
    {
        return view('radianevent::manage.index');
    }
    
    
    public function columns()
    {
        return [
            'identification_number' => 'NIT Emisor',
            'name_seller' => 'Nombre emisor',
            'prefix' => 'Prefijo',
        ];
    }

    
    public function records(Request $request)
    {
        $records = ReceivedDocument::where($request->column, 'like', "%{$request->value}%");

        return new ReceivedDocumentCollection($records->latest()->paginate(config('tenant.items_per_page')));
    }


    public function runEvent(Request $request)
    {
        if ($request->has('all_pending') && $request->all_pending) {
            $pendientes = ReceivedDocument::where(function($q){
                $q->whereNull('rechazo')->orWhere('rechazo', 0);
            })->get();

            $procesados = 0;
            $errores = 0;
            $mensajes = [];

            foreach ($pendientes as $doc) {
                try {
                    // 1. Acuse de Recibo (event_code = 1)
                    if (empty($doc->acu_recibo) || $doc->acu_recibo == 0) {
                        $req = new Request([
                            'id' => $doc->id,
                            'event_code' => '1'
                        ]);
                        $resp = $this->runEvent($req);
                        if (!is_array($resp) || !isset($resp['success']) || !$resp['success']) {
                            throw new \Exception('Error en Acuse de Recibo: ' . (is_array($resp) && isset($resp['message']) ? $resp['message'] : ''));
                        }
                    }
                    // 2. Recepción de Bienes (event_code = 3)
                    if (empty($doc->recepcion_bienes) || $doc->recepcion_bienes == 0) {
                        $req = new Request([
                            'id' => $doc->id,
                            'event_code' => '3'
                        ]);
                        $resp = $this->runEvent($req);
                        if (!is_array($resp) || !isset($resp['success']) || !$resp['success']) {
                            throw new \Exception('Error en Recepción de Bienes: ' . (is_array($resp) && isset($resp['message']) ? $resp['message'] : ''));
                        }
                    }
                    // 3. Aceptación Expresa (event_code = 4)
                    if (empty($doc->aceptacion) || $doc->aceptacion == 0) {
                        $req = new Request([
                            'id' => $doc->id,
                            'event_code' => '4'
                        ]);
                        $resp = $this->runEvent($req);
                        if (!is_array($resp) || !isset($resp['success']) || !$resp['success']) {
                            throw new \Exception('Error en Aceptación Expresa: ' . (is_array($resp) && isset($resp['message']) ? $resp['message'] : ''));
                        }
                    }
                    $procesados++;
                } catch (\Exception $e) {
                    $errores++;
                    $mensajes[] = "Documento ID {$doc->id}: " . $e->getMessage();
                }
            }
            return [
                'success' => true,
                'message' => "Se procesaron $procesados documentos. Errores: $errores",
                'errors' => $mensajes
            ];
        }
        $received_document = ReceivedDocument::with('email_reading_detail')->findOrFail($request->id);
        $from_address = $received_document->email_reading_detail ? $received_document->email_reading_detail->from_address : null;

        $url = "ubl2.1/send-event";
        $company = ServiceCompany::select('identification_number', 'api_token')->firstOrFail();
        $connection_api = new HttpConnectionApi($company->api_token);
        $folder = "radian_reception_documents";
        $filename = $received_document->xml;

        $xml = Storage::disk('tenant')->get($folder.DIRECTORY_SEPARATOR.$filename);
        
        $params = [
            'event_id' => $request->event_code,
            'base64_attacheddocument_name' => $filename,
            'base64_attacheddocument' => base64_encode($xml),
            'sendmail' => true,
            'sendmailtome' => true,
            'email_cc_list' => $from_address ? [['email' => $from_address]] : [],
        ];

        if($request->event_code === '2') $params['type_rejection_id'] = $request->type_rejection_id;

        $send_request_to_api = $connection_api->sendRequestToApi($url, $params, 'POST');

        if(isset($send_request_to_api['errors']))  return $this->getGeneralResponse(false, $connection_api->parseErrorsToString($send_request_to_api['errors']));

        if($send_request_to_api['success'])
        {
            return $this->validateResponseApi($send_request_to_api, $connection_api, $received_document, $request->event_code);
        }

        return $send_request_to_api;
    }


    private function validateResponseApi($send_request_to_api, HttpConnectionApi $connection_api, $received_document, $event_code)
    {
        //parsear respuesta y verificar
        $send_event_update_status_result = $send_request_to_api['ResponseDian']['Envelope']['Body']['SendEventUpdateStatusResponse']['SendEventUpdateStatusResult'];

        if($send_event_update_status_result['IsValid'] == 'true')
        {
            // actualizar datos
            $this->updateStateByEventCode($received_document, $event_code, $send_request_to_api);
            
            return $this->getGeneralResponse(true, 'Resultado del Evento: '.$send_event_update_status_result['StatusMessage']);
        }

        // estado rechazado/errores
        $extract_error_response = $send_event_update_status_result['ErrorMessage']['string'] ?? $send_event_update_status_result['StatusDescription'];
        $error_message_response = is_array($extract_error_response) ?  implode(",", $extract_error_response) : $extract_error_response;

        return $this->getGeneralResponse(false, "Resultado del Evento: {$error_message_response}");
    }

    
    /**
     * 
     * Actualizar estados/datos 
     *
     * @param  ReceivedDocument $received_document
     * @param  string $event_code
     * @param  array $send_request_to_api
     * @return void
     */
    private function updateStateByEventCode($received_document, $event_code, $send_request_to_api)
    {
        $data_update = [];

        // Extraer el CUDE de XmlDocumentKey
        $cude = $send_request_to_api['ResponseDian']['Envelope']['Body']['SendEventUpdateStatusResponse']['SendEventUpdateStatusResult']['XmlDocumentKey'] ?? null;
        
        if (!$cude) {
            // Intentar obtener de la propiedad cude si existe
            $cude = $send_request_to_api['cude'] ?? null;
        }
        switch ($event_code) 
        {
            case '1':
                $data_update = [
                    'acu_recibo' => 1,
                    'response_acu_recibo' => json_encode($send_request_to_api),
                    'cude_acu_recibo' => $cude,
                ];
                break;
            
            case '2':
                $data_update = [
                    'rechazo' => 1,
                    'response_rechazo' => json_encode($send_request_to_api),
                    'cude_rechazo' => $cude,
                ];
                break;

            case '3':
                $data_update = [
                    'rec_bienes' => 1,
                    'response_rec_bienes' => json_encode($send_request_to_api),
                    'cude_rec_bienes' => $cude,
                ];
                break;

            case '4':
                $data_update = [
                    'aceptacion' => 1,
                    'response_aceptacion' => json_encode($send_request_to_api),
                    'cude_aceptacion' => $cude,
                ];
                break;
        }
        
        $received_document->update($data_update);
    }

    
    // public function throwException($message)
    // {
    //     throw new Exception($message);
    // }
    
    public function download($filename)
    {
        return Storage::disk('tenant')->download("radian_reception_documents".DIRECTORY_SEPARATOR.$filename);
    }

    public function publicDownload($filename)
    {
        return Storage::disk('tenant')->download("radian_reception_documents".DIRECTORY_SEPARATOR.$filename);
    }


    /**
     * 
     * Cargar xml
     *
     * @param  Request $request
     * @return array
     */
    public function upload(Request $request)
    {
        if ($request->hasFile('file'))
        {
            try {

                $folder = "radian_reception_documents";
                $file = $request->file('file');
                $file_content = file_get_contents($file);

                $filename = $file->getClientOriginalName(); //xml
                $filename_pdf = str_replace('.xml', '.pdf', $filename); //pdf

                $parse_filename = explode('.', $filename);
                $extension = end($parse_filename);

                // si es pdf, el registro debe existir

                if($extension === 'pdf')
                {
                    $exist_record = ReceivedDocument::where('xml', str_replace('.pdf', '.xml', $filename))->first();

                    if(!$exist_record) return $this->getGeneralResponse(false, 'Debe cargar el xml previamente.');

                    Storage::disk('tenant')->put($folder.DIRECTORY_SEPARATOR.$filename_pdf, $file_content);

                    return $this->getGeneralResponse(true, 'Archivo cargado correctamente.');
                }


                // proceso inicial para cargar xml

                if(Storage::disk('tenant')->exists($folder.DIRECTORY_SEPARATOR.$filename)) throw new Exception('El archivo ya fue cargado');

                $advancedConfig = AdvancedConfiguration::first();
                $showCreditAndContado = $advancedConfig ? $advancedConfig->radian_show_credit_only : true; // true por defecto
                $isCredit = $this->isCreditFromXml($file_content);

                // Si el switch está inactivo (solo crédito) y el documento NO es crédito, omitir
                if (!$showCreditAndContado && !$isCredit) {
                    return [
                        'success' => false,
                        'message' => 'Solo se permiten comprobantes a crédito.'
                    ];
                }

                // enviar api para parsear xml y obtener data
                $company = ServiceCompany::select('identification_number', 'api_token')->firstOrFail();
                $connection_api = new HttpConnectionApi($company->api_token);
                
                $params = [
                    'xml_document' => base64_encode($file_content),
                    'company_idnumber' => $company->identification_number,
                ];

                $url = "process-seller-document-reception";
                $send_request_to_api = $connection_api->sendRequestToApi($url, $params, 'POST');

                if(!$send_request_to_api['success']) throw new Exception($send_request_to_api['message']);
                // enviar api

                // Validar si es documento de crédito
                // if(!$this->isCreditFromXml($file_content)) {
                //     return [
                //         'success' => false,
                //         'message' => 'Solo se permiten comprobantes a crédito.'
                //     ];
                // }

                //subir archivo 
                Storage::disk('tenant')->put($folder.DIRECTORY_SEPARATOR.$filename, $file_content);

                // registrar en bd
                $data = $send_request_to_api['data'];
                $data['xml'] = $filename;
                $data['pdf'] = $filename_pdf;
                
                ReceivedDocument::create($data);

                return [
                    'success' => true,
                    'message' =>  'Archivo cargado',
                    'send_request_to_api' => $send_request_to_api
                ];
                
            } 
            catch (Exception $e) 
            {
                return [
                    'success' => false,
                    'message' =>  $e->getMessage()
                ];
            }
        }
        
        return [
            'success' => false,
            'message' =>  __('app.actions.upload.error'),
        ];
    }

    private function isCreditFromXml($xmlContent)
    {
        // Extraer el XML de la factura desde el CDATA
        $matches = [];
        preg_match('/<Invoice[\s\S]*<\/Invoice>/', $xmlContent, $matches);
        if (!$matches) return false;

        $invoiceXml = $matches[0];
        $invoice = new \SimpleXMLElement($invoiceXml);
        $namespaces = $invoice->getNamespaces(true);

        // Buscar PaymentMeans
        foreach ($invoice->children($namespaces['cac'])->PaymentMeans as $paymentMeans) {
            $id = (string)$paymentMeans->children($namespaces['cbc'])->ID;
            // Solo verifica el ID: 1 = contado, 2 = crédito
            if ($id === '2') {
                return true;
            }
        }
        return false;
    }
}
