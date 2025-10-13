<?php

namespace Modules\Factcolombia1\Http\Controllers\Tenant;

use Modules\Factcolombia1\Traits\Tenant\DocumentTrait;
use Modules\Factcolombia1\Http\Controllers\Controller;
use Modules\Factcolombia1\Http\Requests\Tenant\{
    ConfigurationTypeDocumentRequest,
    ConfigurationUploadLogoRequest,
    ConfigurationCompanyRequest,
    ConfigurationServiceCompanyRequest,
    ConfigurationServiceSoftwareCompanyRequest,
    ConfigurationServiceSoftwarePayrollRequest,
    ConfigurationServiceSoftwareEqDocsRequest,
    ConfigurationServiceCertificateCompanyRequest,
    ConfigurationServiceResolutionCompanyRequest
};
use Illuminate\Http\Request;
use Modules\Factcolombia1\Configuration;
use DB;
use Modules\Factcolombia1\Models\Tenant\{
    TypeIdentityDocument,
    TypeObligation,
    TypeDocument,
    NoteConcept,
    TypeRegime,
    VersionUbl,
    Department,
    Currency,
    Ambient,
    Company,
    Country,
    City
};
use Modules\Factcolombia1\Models\TenantService\{
    Company as ServiceCompany
};
use Carbon\Carbon;
use Modules\Factcolombia1\Models\TenantService\{
    Company as ServiceTenantCompany
};
use Modules\Factcolombia1\Helpers\HttpConnectionApi;


class ConfigurationController extends Controller
{
    use DocumentTrait;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('factcolombia1::configuration.tenant.index');
    }

    public function document()
    {
        return view('factcolombia1::configuration.tenant.documents');
    }

    public function production()
    {
        return view('configuration.tenant.production');
    }

    public function changeAmbient()
    {
        return view('factcolombia1::configuration.tenant.change_ambient');
    }


    /**
     * All
     * @return \Illuminate\Http\Response
     */
    public function all()
    {
        return [
            'typeIdentityDocuments' => TypeIdentityDocument::all(),
            'typeObligations' => TypeObligation::all(),
            'typeDocuments' => TypeDocument::where('name', '!=', 'Factura Electronica de venta')->get(),
            'typeRegimes' => TypeRegime::all(),
            'versionUbls' => VersionUbl::all(),
            'currencies' => Currency::all(),
            'countries' => Country::all(),
            'ambients' => Ambient::all()
        ];
    }

    /**
     * Company
     * @return \Illuminate\Http\Response
     */
    public function company()
    {
        /*$company = Company::query()
            ->with('currency')
            ->firstOrFail();*/

        $company = ServiceCompany::first();

        //        $file = fopen("C:\\DEBUG.TXT", "w");
        //        fwrite($file, json_encode($company));
        //        fclose($file);

        $company->alert_certificate = Carbon::parse($company->certificate_date_end)->subMonth(1)->lt(Carbon::now());

        $company['resolution_date'] = date("Y-m-d");
        $company['date_from'] = date("Y-m-d");
        $company['date_to'] = date("Y-m-d", strtotime("+2 days"));

        return $company;
    }

    /**
     * Countries
     * @return \Illuminate\Http\Response
     */
    public function countries()
    {
        return Country::all();
    }

    /**
     * Departments
     * @param  \App\Models\Tenant\Country $country
     * @return \Illuminate\Http\Response
     */
    public function departments(Country $country)
    {
        return Department::query()
            ->where('country_id', $country->id)
            ->get();
    }

    /**
     * Cities
     * @param  \App\Models\Tenant\Department $department
     * @return \Illuminate\Http\Response
     */
    public function cities(Department $department)
    {
        return City::query()
            ->where('department_id', $department->id)
            ->get();
    }

    /**
     * Concepts
     * @param  \App\Models\Tenant\TypeDocument $typeDocument
     * @return \Illuminate\Http\Response
     */
    public function concepts(TypeDocument $typeDocument)
    {
        return NoteConcept::query()
            ->where('type_document_id', $typeDocument->id)
            ->get();
    }

    /**
     * Update company
     * @param  \App\Http\Requests\Tenant\ConfigurationCompanyRequest $request
     * @param  \App\Models\Tenant\Company                     $company
     * @return \Illuminate\Http\Response
     */
    public function updateCompany(ConfigurationCompanyRequest $request, Company $company)
    {
        if ($request->hasFile('certificate')) $this->uploadCertificate($request->certificate);

        $company->update([
            'type_identity_document_id' => $request->type_identity_document_id,
            'short_name' => $request->short_name,
            'email' => $request->email,
            //  'country_id' => $request->country_id,
            'department_id' => $request->department_id,
            'city_id' => $request->city_id,
            'address' => $request->address,
            'phone' => $request->phone,
            //'currency_id' => $request->currency_id,
            'type_regime_id' => $request->type_regime_id,
            // 'economic_activity_code' => $request->economic_activity_code,
            'ica_rate' => $request->ica_rate,
            'type_obligation_id' => $request->type_obligation_id,
            'version_ubl_id' => $request->version_ubl_id,
            'ambient_id' => $request->ambient_id,
            'software_identifier' => $request->software_identifier,
            'software_password' => $request->software_password,
            'pin' => $request->pin,
            'certificate_name' => $request->certificate_name,
            'certificate_password' => $request->certificate_password,
            'certificate_date_end' => $request->certificate_date_end
        ]);

        return [
            'success' => true
        ];
    }

    /**
     * Update type document
     * @param  \App\Http\Requests\Tenant\ConfigurationTypeDocumentRequest $request
     * @param  \App\Models\Tenant\TypeDocument                     $typeDocument
     * @return \Illuminate\Http\Response
     */
    public function updateTypeDocument(ConfigurationTypeDocumentRequest $request, TypeDocument $typeDocument)
    {

        // $base_url = env("SERVICE_FACT", "");
        $base_url = config("tenant.service_fact", "");
        $servicecompany = ServiceCompany::firstOrFail();
        $typeDocument->update([
            'resolution_number' => $request->resolution_number,
            'resolution_date' => $request->resolution_date,
            'resolution_date_end' => $request->resolution_date_end,
            'technical_key' => $request->technical_key,
            'prefix' => mb_strtoupper($request->prefix),
            'from' => $request->from,
            'to' => $request->to,
            'generated' => $request->generated,
            'description' => $request->description,
            'show_in_establishments' => $request->show_in_establishments,
            'establishment_ids' => $request->establishment_ids,
        ]);

        $ch = curl_init("{$base_url}ubl2.1/config/generateddocuments");
        $data = [
            'identification_number' => $servicecompany->identification_number,
            'type_document_id' => $typeDocument->code,
            'prefix' => mb_strtoupper($request->prefix),
            'number' => $request->generated
        ];

        $data_initial_number = json_encode($data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, ($data_initial_number));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
            "Authorization: Bearer {$servicecompany->api_token}"
        ));
        $response_initial_number = curl_exec($ch);

        //return json_encode($response_initial_number);

        $err = curl_error($ch);
        $respuesta = json_decode($response_initial_number);

        if ($err) {
            return [
                'message' => "Error en peticion Api.",
                'success' => false,
            ];
        } else {
            if (property_exists($respuesta, 'success')) {
                return [
                    'message' => "Se ajusto satisfactoriamente el numero inicial.",
                    'success' => true,
                ];
            } else {
                return [
                    'message' => "Error en validacion de datos Api.",
                    'success' => false,
                ];
            }
        }
    }

    /**
     * Upload logo
     * @param  \App\Http\Requests\Tenant\ConfigurationUploadLogoRequest $request
     * @return \Illuminate\Http\Response
     */
    public function uploadLogo(ConfigurationUploadLogoRequest $request)
    {
        $base_url = config("tenant.service_fact", "");
        //        $base_url = env("SERVICE_FACT", "");

        $company = Company::firstOrFail();
        $servicecompany = ServiceCompany::firstOrFail();
        $file = $request->file('file');

        $name = "logo_.{$company->identification_number}.{$file->getClientOriginalExtension()}";

        $file->storeAs('public/uploads/logos', $name);

        $company->logo = $name;
        $company->save();

        //--------send logo------------------

        //        $file = fopen("C:\\DEBUG.txt", "w");
        //        fwrite($file, storage_path('app/public/uploads/logos/'.$name));
        //        fwrite($file, base64_encode(file_get_contents(storage_path('app/public/uploads/logos/'.$name))));
        //        fclose($file);

        $ch = curl_init("{$base_url}ubl2.1/config/logo");
        $data = [
            "logo" => base64_encode(file_get_contents(storage_path('app/public/uploads/logos/' . $name))),
        ];
        $data_logo = json_encode($data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, ($data_logo));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
            "Authorization: Bearer {$servicecompany->api_token}"
        ));

        $response_logo = curl_exec($ch);
        $err = curl_error($ch);
        $respuesta = json_decode($response_logo);

        if ($err) {
            return [
                'message' => "Error en peticion Api.",
                'success' => false,
                'logo' => ''
            ];
        } else {

            if (property_exists($respuesta, 'success')) {
                return [
                    'message' => "Se guardaron los cambios.",
                    'success' => true,
                    'logo' => $response_logo
                ];
            } else {
                return [
                    'message' => "Error en validacion de datos Api.",
                    'success' => false,
                    'respuesta' => $respuesta,
                    'data' => $data_logo
                ];
            }
        }
    }

    public function storeServiceCompanie(ConfigurationServiceCompanyRequest $request)
    {
        $company = ServiceCompany::firstOrFail();
        // $base_url = env("SERVICE_FACT", "");
        $base_url = config("tenant.service_fact", "");



        //----send software----
        $ch = curl_init("{$base_url}ubl2.1/config/software");
        $data = [
            "id" => $request->id_software,
            "pin" => $request->pin_software,
            "url" => $request->url_software,
        ];
        $data_software = json_encode($data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, ($data_software));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
            "Authorization: Bearer {$company->api_token}"
        ));
        $response_software = curl_exec($ch);
        $company->response_software = $response_software;

        //----------------------

        //--------send cerificate------------------

        $ch2 = curl_init("{$base_url}ubl2.1/config/certificate");
        $data = [
            "certificate" => $request->certificate64,
            "password" =>  $request->password_certificate //"Nqx4FAZ6kD"//$request->password
        ];
        $data_certificate = json_encode($data);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch2, CURLOPT_POSTFIELDS, ($data_certificate));
        curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch2, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
            "Authorization: Bearer {$company->api_token}"
        ));

        $response_certificate = curl_exec($ch2);
        $company->response_certificate = $response_certificate;

        //------------------------------------------

        //----send resolution--------
        $ch3 = curl_init("{$base_url}ubl2.1/config/resolution");
        $data = [
            "type_document_id" => $request->type_document_id['id'],
            "prefix" => $request->prefix,
            "resolution" => $request->resolution,
            "resolution_date" => $request->resolution_date,
            "technical_key" => $request->technical_key,
            "from" => $request->from,
            "to" => $request->to,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to
        ];
        $data_resolution = json_encode($data);
        curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch3, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch3, CURLOPT_POSTFIELDS, ($data_resolution));
        curl_setopt($ch3, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch3, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch3, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
            "Authorization: Bearer {$company->api_token}"
        ));


        $response_resolution = curl_exec($ch3);
        $company->response_resolution = $response_resolution;

        $company->save();


        return [
            'message' => "Se guardaron los cambios.",
            'success' => true,
            //'software' => $response_software,
            'cetificate' => $response_certificate,
            //'resolution' => $response_resolution
        ];
    }
    public function storeServiceCompanieResolution(Request $request)
    {
        try {
            $company = ServiceCompany::firstOrFail();
            $base_url = config("tenant.service_fact", "");

            // Validaciones básicas
            $request->validate([
                'type_document_id' => 'required|integer',
                'resolution_number' => 'required|string',
                'prefix' => 'required|string|max:4',
                'resolution_date' => 'required|date',
                'resolution_date_end' => 'required|date',
                'technical_key' => 'required|string',
                'from' => 'required|integer',
                'to' => 'required|integer',
            ]);

            // Obtener el tipo de documento para obtener el código
            $typeDocument = collect($this->getTypeDocuments())->firstWhere('id', $request->type_document_id);

            if (!$typeDocument) {
                return [
                    'message' => 'Tipo de documento no válido.',
                    'success' => false,
                ];
            }

            // Preparar datos para la API
            $ch3 = curl_init("{$base_url}ubl2.1/config/resolution");
            $data = [
                "delete_all_type_resolutions" => false,
                "type_document_id" => $typeDocument['code'],
                "prefix" => strtoupper($request->prefix),
                "resolution" => $request->resolution_number,
                "resolution_date" => $request->resolution_date,
                "technical_key" => $request->technical_key,
                "from" => $request->from,
                "to" => $request->to,
                'date_from' => $request->resolution_date,
                'date_to' => $request->resolution_date_end,
            ];

            $data_resolution = json_encode($data);
            curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch3, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch3, CURLOPT_POSTFIELDS, $data_resolution);
            curl_setopt($ch3, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch3, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch3, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Accept: application/json',
                "Authorization: Bearer {$company->api_token}"
            ));

            $response_resolution = curl_exec($ch3);
            $err = curl_error($ch3);
            curl_close($ch3);
            $respuesta = json_decode($response_resolution);

            if ($err) {
                return [
                    'message' => "Error en peticion Api Resolution.",
                    'success' => false,
                ];
            }

            if (property_exists($respuesta, 'success')) {
                // Guardar en la base de datos local
                TypeDocument::updateOrCreate([
                    'code' => $typeDocument['code'],
                    'prefix' => strtoupper($request->prefix),
                    'resolution_number' => $request->resolution_number,
                ], [
                    'resolution_date' => $request->resolution_date,
                    'resolution_date_end' => $request->resolution_date_end,
                    'technical_key' => $request->technical_key,
                    'from' => $request->from,
                    'to' => $request->to,
                    'generated' => $request->generated ?? 0,
                    'name' => $typeDocument['name'],
                    'template' => 'face_f',
                    'description' => $request->description ?? "Resolución {$request->resolution_number}"
                ]);

                return [
                    'message' => "Resolución creada exitosamente.",
                    'success' => true,
                ];
            } else {
                return [
                    'message' => "Error en validacion de datos Api.",
                    'success' => false,
                    'response' => $response_resolution
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener tipos de documentos disponibles
     */
    private function getTypeDocuments()
    {
        return [
            ['id' => 1, 'name' => "Factura de Venta Nacional", 'code' => '1'],
            ['id' => 4, 'name' => "Nota Crédito", 'code' => '4'],
            ['id' => 5, 'name' => "Nota Débito", 'code' => '5'],
            ['id' => 9, 'name' => "Nomina Individual", 'code' => '9'],
            ['id' => 10, 'name' => "Nomina Individual de Ajuste", 'code' => '10'],
            ['id' => 11, 'name' => "Documento Soporte Electrónico", 'code' => '11'],
            ['id' => 13, 'name' => "Nota de Ajuste al Documento Soporte Electrónico", 'code' => '13'],
            ['id' => 15, 'name' => "Documento Equivalente", 'code' => '15'],
            ['id' => 26, 'name' => "Nota de crédito al Documento Equivalente", 'code' => '26']
        ];
    }

    public function changeEnvironmentProduction(string $environment)
    {
        $company = ServiceCompany::firstOrFail();
        $base_url = config("tenant.service_fact", "");
        if ($environment === 'P') {
            return $this->changeInvoiceToProduction($company, $base_url);
        } elseif ($environment === 'eqdocsP') {
            return $this->changeEqDocsToProduction($company, $base_url);
        } else {
            //        $base_url = env("SERVICE_FACT", "");
            $ch = curl_init("{$base_url}ubl2.1/config/environment");

            $data = $this->getDataByTypeEnvironment($environment);

            $data_production = json_encode($data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, ($data_production));
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Accept: application/json',
                "Authorization: Bearer {$company->api_token}"
            ));
            $response_change = curl_exec($ch);
            $err = curl_error($ch);
            $respuesta = json_decode($response_change);

            if ($err) {
                return [
                    'message' => "Error en peticion Api.",
                    'success' => false,
                ];
            } else {
                if (property_exists($respuesta, 'message')) {
                    return $this->updateTypeEnvironmentCompany($environment, $company);
                } else {
                    return [
                        'message' => "Error en validacion de datos Api.",
                        'success' => false,
                    ];
                }
            }
        }
    }

    public function changeEnvironmentOnly(string $environment)
    {
        $company = ServiceCompany::firstOrFail();
        $base_url = config("tenant.service_fact", "");
        
        $ch = curl_init("{$base_url}ubl2.1/config/environment");
        $data = $this->getDataByTypeEnvironment($environment);
        
        $data_production = json_encode($data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, ($data_production));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
            "Authorization: Bearer {$company->api_token}"
        ));
        
        $response_change = curl_exec($ch);
        $err = curl_error($ch);
        $respuesta = json_decode($response_change);

        if ($err) {
            return response()->json([
                'message' => "Error en peticion Api.",
                'success' => false,
            ], 500);
        } else {
            if (property_exists($respuesta, 'message')) {
                return $this->updateTypeEnvironmentCompany($environment, $company);
            } else {
                return response()->json([
                    'message' => "Error en validacion de datos Api.",
                    'success' => false,
                ], 400);
            }
        }
    }
    /**
     * Cambiar facturación a producción con validación de Set de Pruebas
     */
    private function changeInvoiceToProduction($company, $base_url)
    {
        $testSetId = trim($company->test_id ?? '');

        if (empty($testSetId) || $testSetId === 'no_test_set_id' || strlen($testSetId) < 3) {
            return response()->json([
                'message' => "TestSetId inválido o no configurado. Valor actual: '{$testSetId}'. Configure un TestSetId válido proporcionado por la DIAN.",
                'success' => false,
            ], 400);
        }
        // Cambiar a habilitación
        $ch = curl_init("{$base_url}ubl2.1/config/environment");
        $data_habilitacion = json_encode(["type_environment_id" => 2]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_habilitacion);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
            "Authorization: Bearer {$company->api_token}"
        ));
        curl_exec($ch);
        curl_close($ch);

        $consecutive = 990000001;
        try {
            $ch = curl_init("{$base_url}ubl2.1/next-consecutive");
            $body = [
                'type_document_id' => '1',
                'prefix' => 'SETP'
            ];
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                "Authorization: Bearer {$company->api_token}"
            ]);
            $response = curl_exec($ch);
            $data = json_decode($response, true);
            if (isset($data['number'])) $consecutive = (int)$data['number'];
            curl_close($ch);
        } catch (\Exception $e) {
        }

        $json = [
            "number" => $consecutive,
            "type_document_id" => 1,
            "date" => now()->format('Y-m-d'),
            "time" => now()->format('H:i:s'),
            "resolution_number" => 18760000001,
            "prefix" => "SETP",
            "customer" => [
                "identification_number" => "900428042",
                "dv" => 2,
                "name" => "TAMPAC TECNOLOGÍA EN AUTOMATIZACIÓN SAS"
            ],
            "payment_form" => [
                "payment_form_id" => 1,
                "payment_method_id" => 30,
                "payment_due_date" => now()->format('Y-m-d'),
                "duration_measure" => "30"
            ],
            "legal_monetary_totals" => [
                "line_extension_amount" => "2000.00",
                "tax_exclusive_amount" => "2000.00",
                "tax_inclusive_amount" => "2380.00",
                "payable_amount" => "2380.00"
            ],
            "tax_totals" => [
                [
                    "tax_id" => 1,
                    "tax_amount" => "380.00",
                    "percent" => "19",
                    "taxable_amount" => "2000.00"
                ]
            ],
            "invoice_lines" => [
                [
                    "unit_measure_id" => 70,
                    "invoiced_quantity" => "1",
                    "line_extension_amount" => "1000.00",
                    "free_of_charge_indicator" => false,
                    "description" => "Producto de prueba 1",
                    "code" => "PRUEBA1",
                    "type_item_identification_id" => 4,
                    "price_amount" => "1000.00",
                    "base_quantity" => "1",
                    "tax_totals" => [
                        [
                            "tax_id" => 1,
                            "tax_amount" => "190.00",
                            "taxable_amount" => "1000.00",
                            "percent" => "19.00"
                        ]
                    ]
                ],
                [
                    "unit_measure_id" => 70,
                    "invoiced_quantity" => "1",
                    "line_extension_amount" => "1000.00",
                    "free_of_charge_indicator" => false,
                    "description" => "Producto de prueba 2",
                    "code" => "PRUEBA2",
                    "type_item_identification_id" => 4,
                    "price_amount" => "1000.00",
                    "base_quantity" => "1",
                    "tax_totals" => [
                        [
                            "tax_id" => 1,
                            "tax_amount" => "190.00",
                            "taxable_amount" => "1000.00",
                            "percent" => "19.00"
                        ]
                    ]
                ]
            ]
        ];

        // PASO 1: Enviar factura al set de pruebas
        $ch = curl_init("{$base_url}ubl2.1/invoice/{$testSetId}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            "Authorization: Bearer {$company->api_token}"
        ]);
        $response = curl_exec($ch);
        // \Log::info("Response from DIAN API: " . $response);
        $result = json_decode($response, true);
        curl_close($ch);

        // Mapear el ZipKey de la respuesta
        $zipkey = null;
        if (isset($result['ResponseDian']['Envelope']['Body']['SendTestSetAsyncResponse']['SendTestSetAsyncResult']['ZipKey'])) {
            $zipkey = $result['ResponseDian']['Envelope']['Body']['SendTestSetAsyncResponse']['SendTestSetAsyncResult']['ZipKey'];
        }

        if (!$zipkey) {
            $mensaje = $result['message'] ?? 'No se obtuvo ZipKey de la respuesta de la DIAN.';
            return [
                'message' => $mensaje,
                'success' => false,
                'response_data' => $result
            ];
        }

        // PASO 2: Consultar el estado del ZIP
        $ch = curl_init("{$base_url}ubl2.1/status/zip/{$zipkey}");
        $body = [
            "sendmail" => false,
            "sendmailtome" => false,
            "is_payroll" => false,
            "is_eqdoc" => false
        ];
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            "Authorization: Bearer {$company->api_token}"
        ]);
        $zip_response = curl_exec($ch);
        $zip_status = json_decode($zip_response, true);
        curl_close($ch);
        // \Log::info("Zip Status Response: " . $zip_response);

        // Mapear la respuesta del estado del ZIP
        $dian_response = null;
        $is_valid = false;
        $status_code = null;
        $status_description = '';
        $status_message = '';
        $error_messages = [];
        $notifications = [];
        $rejections = [];
        $zip_success = false;

        // Verificar estructura de respuesta del ZIP
        if (isset($zip_status['ResponseDian']['Envelope']['Body']['GetStatusZipResponse']['GetStatusZipResult']['DianResponse'])) {
            $dian_response = $zip_status['ResponseDian']['Envelope']['Body']['GetStatusZipResponse']['GetStatusZipResult']['DianResponse'];

            $is_valid = isset($dian_response['IsValid']) ? ($dian_response['IsValid'] === 'true') : false;
            $status_code = $dian_response['StatusCode'] ?? null;
            $status_description = $dian_response['StatusDescription'] ?? '';
            $status_message = $dian_response['StatusMessage'] ?? '';

            // Extraer y clasificar mensajes de error
            if (isset($dian_response['ErrorMessage']['string']) && is_array($dian_response['ErrorMessage']['string'])) {
                $error_messages = $dian_response['ErrorMessage']['string'];

                // Separar notificaciones de rechazos
                foreach ($error_messages as $message) {
                    if (strpos($message, 'Notificación:') !== false) {
                        $notifications[] = $message;
                    } elseif (strpos($message, 'Rechazo:') !== false) {
                        $rejections[] = $message;
                    } else {
                        $error_messages[] = $message;
                    }
                }
            } elseif (isset($dian_response['ErrorMessage']) && is_string($dian_response['ErrorMessage'])) {
                $error_messages = [$dian_response['ErrorMessage']];
            }

            // Determinar si fue exitoso:
            // - IsValid debe ser true
            // - StatusCode debe ser "00" (éxito) o "0"
            // - Si hay rechazos, es un fallo
            $zip_success = $is_valid && ($status_code === '00' || $status_code === '0') && empty($rejections);
        }

        // Preparar mensaje de respuesta
        $zip_message = '';
        if ($zip_success) {
            $zip_message = $status_description;

            if (!empty($notifications)) {
                $zip_message .= "\n\nNotificaciones informativas:";
                foreach ($notifications as $index => $notification) {
                    $zip_message .= "\n" . ($index + 1) . ". " . $notification;
                }
            }

            if (!empty($status_message)) {
                $zip_message .= "\n\n" . $status_message;
            }
        } else {
            // Manejar diferentes tipos de error
            switch ($status_code) {
                case '2':
                    $zip_message = "Error en Set de Pruebas: {$status_description}";
                    break;

                case '99':
                    $zip_message = "Errores de Validación: {$status_description}";
                    break;

                default:
                    $zip_message = $status_description ?: 'No se pudo validar el Set de Pruebas correctamente.';
                    break;
            }

            // Agregar rechazos si existen
            if (!empty($rejections)) {
                $zip_message .= "\n\nRechazos encontrados:";
                foreach ($rejections as $index => $rejection) {
                    $zip_message .= "\n" . ($index + 1) . ". " . $rejection;
                }
            }

            // Agregar otros errores
            if (!empty($error_messages)) {
                $zip_message .= "\n\nErrores adicionales:";
                foreach ($error_messages as $index => $error) {
                    $zip_message .= "\n" . ($index + 1) . ". " . $error;
                }
            }
        }

        // Si el set de pruebas no es válido, no continuar a producción
        if (!$zip_success) {
            return [
                'message' => $zip_message,
                'success' => false,
                'zipkey' => $zipkey,
                'test_set_id' => $testSetId,
                'zip_details' => [
                    'is_valid' => $is_valid,
                    'status_code' => $status_code,
                    'status_description' => $status_description,
                    'status_message' => $status_message,
                    'notifications' => $notifications,
                    'rejections' => $rejections,
                    'other_errors' => $error_messages,
                    'notifications_count' => count($notifications),
                    'rejections_count' => count($rejections)
                ]
            ];
        }

        // 3. Cambiar a producción solo si ambos pasos fueron exitosos
        $ch = curl_init("{$base_url}ubl2.1/config/environment");
        $data_produccion = json_encode(["type_environment_id" => 1]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_produccion);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
            "Authorization: Bearer {$company->api_token}"
        ));
        $response_change = curl_exec($ch);
        $err = curl_error($ch);
        $respuesta = json_decode($response_change);

        if ($err) {
            return [
                'message' => "Error en peticion Api.",
                'success' => false,
                'zipkey' => $zipkey,
                'zip_status' => $zip_status
            ];
        } else {
            if (property_exists($respuesta, 'message')) {
                $update = $this->updateTypeEnvironmentCompany('P', $company);
                return [
                    'success' => true,
                    'message' => $update['message'],
                    'zipkey' => $zipkey,
                    'zip_status' => $zip_status
                ];
            } else {
                return [
                    'message' => "Error en validacion de datos Api.",
                    'success' => false,
                    'zipkey' => $zipkey,
                    'zip_status' => $zip_status
                ];
            }
        }
    }

    /**
     * Cambiar documentos equivalentes a producción con validación de Set de Pruebas
     */
    private function changeEqDocsToProduction($company, $base_url)
    {
        $testSetId = trim($company->test_set_id_eqdocs ?? '');

        if (empty($testSetId) || $testSetId === 'no_test_set_id' || strlen($testSetId) < 3) {
            return response()->json([
                'message' => "TestSetId de documentos equivalentes inválido o no configurado. Valor actual: '{$testSetId}'. Configure un TestSetId válido proporcionado por la DIAN.",
                'success' => false,
            ], 400);
        }

        // 1. Cambiar a habilitación
        $ch = curl_init("{$base_url}ubl2.1/config/environment");
        $data_habilitacion = json_encode(["eqdocs_type_environment_id" => 2]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_habilitacion);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
            "Authorization: Bearer {$company->api_token}"
        ));
        curl_exec($ch);
        curl_close($ch);

        // 2. Obtener consecutivo
        $consecutive = 1;
        try {
            $ch = curl_init("{$base_url}ubl2.1/next-consecutive");
            $body = [
                'type_document_id' => '15',
                'prefix' => 'EPOS'
            ];
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                "Authorization: Bearer {$company->api_token}"
            ]);
            $response = curl_exec($ch);
            $data = json_decode($response, true);
            if (isset($data['number'])) $consecutive = (int)$data['number'];
            curl_close($ch);
        } catch (\Exception $e) {
        }

        // 3. JSON para documento equivalente de prueba
        $json = [
            "number" => $consecutive,
            "type_document_id" => 15,
            "date" => now()->format('Y-m-d'),
            "postal_zone_code" => "630003",
            "resolution_number" => "18760000001",
            "prefix" => "EPOS",
            "software_manufacturer" => [
                "name" => "ALEXANDER OBANDO LONDONO",
                "business_name" => "TORRE SOFTWARE",
                "software_name" => "BABEL"
            ],
            "buyer_benefits" => [
                "code" => "89008003",
                "name" => "INVERSIONES DAVAL SAS",
                "points" => "100"
            ],
            "cash_information" => [
                "plate_number" => "DF-000-12345",
                "location" => "HOTEL OVERLOOK RECEPCION",
                "cashier" => "JACK TORRANCE",
                "cash_type" => "CAJA PRINCIPAL",
                "sales_code" => "EPOS1",
                "subtotal" => "1000000.00"
            ],
            "customer" => [
                "identification_number" => 89008003,
                "dv" => 2,
                "name" => "INVERSIONES DAVAL SAS"
            ],
            "payment_form" => [
                "payment_form_id" => 1,
                "payment_method_id" => 30,
                "payment_due_date" => now()->format('Y-m-d'),
                "duration_measure" => "0"
            ],
            "legal_monetary_totals" => [
                "line_extension_amount" => "840336.134",
                "tax_exclusive_amount" => "840336.134",
                "tax_inclusive_amount" => "1000000.00",
                "payable_amount" => "1000000.00"
            ],
            "tax_totals" => [
                [
                    "tax_id" => 1,
                    "tax_amount" => "159663.865",
                    "percent" => "19.00",
                    "taxable_amount" => "840336.134"
                ]
            ],
            "invoice_lines" => [
                [
                    "unit_measure_id" => 70,
                    "invoiced_quantity" => "1",
                    "line_extension_amount" => "840336.134",
                    "free_of_charge_indicator" => false,
                    "tax_totals" => [
                        [
                            "tax_id" => 1,
                            "tax_amount" => "159663.865",
                            "taxable_amount" => "840336.134",
                            "percent" => "19.00"
                        ]
                    ],
                    "description" => "COMISION POR SERVICIOS",
                    "notes" => "ESTA ES UNA PRUEBA DE NOTA DE DETALLE DE LINEA.",
                    "code" => "COMISION",
                    "type_item_identification_id" => 4,
                    "price_amount" => "1000000.00",
                    "base_quantity" => "1"
                ]
            ]
        ];

        // PASO 4: Enviar documento equivalente al set de pruebas
        $ch = curl_init("{$base_url}ubl2.1/eqdoc/{$testSetId}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            "Authorization: Bearer {$company->api_token}"
        ]);
        $response = curl_exec($ch);
        // \Log::info("Response from DIAN API (EqDocs): " . $response);
        $result = json_decode($response, true);
        curl_close($ch);

        // Mapear el ZipKey de la respuesta
        $zipkey = null;
        if (isset($result['ResponseDian']['Envelope']['Body']['SendTestSetAsyncResponse']['SendTestSetAsyncResult']['ZipKey'])) {
            $zipkey = $result['ResponseDian']['Envelope']['Body']['SendTestSetAsyncResponse']['SendTestSetAsyncResult']['ZipKey'];
        }

        if (!$zipkey) {
            $mensaje = $result['message'] ?? 'No se obtuvo ZipKey de la respuesta de la DIAN para documentos equivalentes.';
            return [
                'message' => $mensaje,
                'success' => false,
                'response_data' => $result
            ];
        }

        // PASO 5: Consultar el estado del ZIP
        $ch = curl_init("{$base_url}ubl2.1/status/zip/{$zipkey}");
        $body = [
            "sendmail" => false,
            "sendmailtome" => false,
            "is_payroll" => false,
            "is_eqdoc" => true
        ];
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            "Authorization: Bearer {$company->api_token}"
        ]);
        $zip_response = curl_exec($ch);
        $zip_status = json_decode($zip_response, true);
        curl_close($ch);
        // \Log::info("Zip Status Response (EqDocs): " . $zip_response);

        // Mapear la respuesta del estado del ZIP (mismo procesamiento que facturas)
        $dian_response = null;
        $is_valid = false;
        $status_code = null;
        $status_description = '';
        $status_message = '';
        $error_messages = [];
        $notifications = [];
        $rejections = [];
        $zip_success = false;

        // Verificar estructura de respuesta del ZIP
        if (isset($zip_status['ResponseDian']['Envelope']['Body']['GetStatusZipResponse']['GetStatusZipResult']['DianResponse'])) {
            $dian_response = $zip_status['ResponseDian']['Envelope']['Body']['GetStatusZipResponse']['GetStatusZipResult']['DianResponse'];

            $is_valid = isset($dian_response['IsValid']) ? ($dian_response['IsValid'] === 'true') : false;
            $status_code = $dian_response['StatusCode'] ?? null;
            $status_description = $dian_response['StatusDescription'] ?? '';
            $status_message = $dian_response['StatusMessage'] ?? '';

            // Extraer y clasificar mensajes de error
            if (isset($dian_response['ErrorMessage']['string']) && is_array($dian_response['ErrorMessage']['string'])) {
                $error_messages = $dian_response['ErrorMessage']['string'];

                // Separar notificaciones de rechazos
                foreach ($error_messages as $message) {
                    if (strpos($message, 'Notificación:') !== false) {
                        $notifications[] = $message;
                    } elseif (strpos($message, 'Rechazo:') !== false) {
                        $rejections[] = $message;
                    } else {
                        $error_messages[] = $message;
                    }
                }
            } elseif (isset($dian_response['ErrorMessage']) && is_string($dian_response['ErrorMessage'])) {
                $error_messages = [$dian_response['ErrorMessage']];
            }

            $zip_success = $is_valid && ($status_code === '00' || $status_code === '0') && empty($rejections);
        }

        // Preparar mensaje de respuesta
        $zip_message = '';
        if ($zip_success) {
            $zip_message = $status_description;

            if (!empty($notifications)) {
                $zip_message .= "\n\nNotificaciones informativas:";
                foreach ($notifications as $index => $notification) {
                    $zip_message .= "\n" . ($index + 1) . ". " . $notification;
                }
            }

            if (!empty($status_message)) {
                $zip_message .= "\n\n" . $status_message;
            }
        } else {
            switch ($status_code) {
                case '2':
                    $zip_message = "Error en Set de Pruebas de documentos equivalentes: {$status_description}";
                    break;

                case '99':
                    $zip_message = "Errores de Validación en documentos equivalentes: {$status_description}";
                    break;

                default:
                    $zip_message = $status_description ?: 'No se pudo validar el Set de Pruebas de documentos equivalentes correctamente.';
                    break;
            }

            // Agregar rechazos si existen
            if (!empty($rejections)) {
                $zip_message .= "\n\nRechazos encontrados:";
                foreach ($rejections as $index => $rejection) {
                    $zip_message .= "\n" . ($index + 1) . ". " . $rejection;
                }
            }

            // Agregar otros errores
            if (!empty($error_messages)) {
                $zip_message .= "\n\nErrores adicionales:";
                foreach ($error_messages as $index => $error) {
                    $zip_message .= "\n" . ($index + 1) . ". " . $error;
                }
            }
        }

        // Si el set de pruebas no es válido, no continuar a producción
        if (!$zip_success) {
            return [
                'message' => $zip_message,
                'success' => false,
                'zipkey' => $zipkey,
                'test_set_id' => $testSetId,
                'zip_details' => [
                    'is_valid' => $is_valid,
                    'status_code' => $status_code,
                    'status_description' => $status_description,
                    'status_message' => $status_message,
                    'notifications' => $notifications,
                    'rejections' => $rejections,
                    'other_errors' => $error_messages,
                    'notifications_count' => count($notifications),
                    'rejections_count' => count($rejections)
                ]
            ];
        }

        // 6. Cambiar a producción solo si ambos pasos fueron exitosos
        $ch = curl_init("{$base_url}ubl2.1/config/environment");
        $data_produccion = json_encode(["eqdocs_type_environment_id" => 1]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_produccion);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
            "Authorization: Bearer {$company->api_token}"
        ));
        $response_change = curl_exec($ch);
        $err = curl_error($ch);
        $respuesta = json_decode($response_change);

        if ($err) {
            return [
                'message' => "Error en peticion Api para documentos equivalentes.",
                'success' => false,
                'zipkey' => $zipkey,
                'zip_status' => $zip_status
            ];
        } else {
            if (property_exists($respuesta, 'message')) {
                $update = $this->updateTypeEnvironmentCompany('eqdocsP', $company);
                return [
                    'success' => true,
                    'message' => $update['message'],
                    'zipkey' => $zipkey,
                    'zip_status' => $zip_status
                ];
            } else {
                return [
                    'message' => "Error en validacion de datos Api para documentos equivalentes.",
                    'success' => false,
                    'zipkey' => $zipkey,
                    'zip_status' => $zip_status
                ];
            }
        }
    }

    /**
     * Actualizar tipo de entorno empresa facturacion/nomina
     *
     * @param  $environment
     * @param  $company
     * @return array
     */
    private function updateTypeEnvironmentCompany($environment, $company)
    {
        $message = null;

        switch ($environment) {

            case 'P':
                $company->type_environment_id = 1;
                $message = 'Se cambio satisfactoriamente a ambiente de PRODUCCIÓN.';
                break;

            case 'H':
                $company->type_environment_id = 2;
                $message = 'Se cambio satisfactoriamente a HABILITACIÓN.';
                break;

            case 'payrollP':
                $company->payroll_type_environment_id = 1;
                $message = 'Se cambio satisfactoriamente a ambiente de PRODUCCIÓN.';
                break;

            case 'payrollH':
                $company->payroll_type_environment_id = 2;
                $message = 'Se cambio satisfactoriamente a HABILITACIÓN.';
                break;

            case 'eqdocsP':
                $company->eqdocs_type_environment_id = 1;
                $message = 'Se cambio satisfactoriamente a ambiente de PRODUCCIÓN.';
                break;

            case 'eqdocsH':
                $company->eqdocs_type_environment_id = 2;
                $message = 'Se cambio satisfactoriamente a HABILITACIÓN.';
                break;
        }

        $company->save();

        return [
            'success' => true,
            'message' => $message,
        ];
    }

    /**
     *
     * Retorna arreglo con tipo de entorno a actualizar facturacion/nomina
     *
     * @param  string $environment
     * @return array
     */
    private function getDataByTypeEnvironment($environment)
    {

        switch ($environment) {
            case 'P':
                $data = [
                    "type_environment_id" => 1,
                ];
                break;
            case 'H':
                $data = [
                    "type_environment_id" => 2,
                ];
                break;
            case 'payrollP':
                $data = [
                    "payroll_type_environment_id" => 1,
                ];
                break;
            case 'payrollH':
                $data = [
                    "payroll_type_environment_id" => 2,
                ];
                break;
            case 'eqdocsP':
                $data = [
                    "eqdocs_type_environment_id" => 1,
                ];
                break;
            case 'eqdocsH':
                $data = [
                    "eqdocs_type_environment_id" => 2,
                ];
                break;
        }
        return $data;
    }

    public function queryTechnicalKey(Request $request)
    {
        $company = ServiceCompany::firstOrFail();
        $base_url = config("tenant.service_fact", "");

        // Recibe el tipo desde el frontend
        $type = $request->input('type', 'invoice'); // valores: invoice, payroll, eqdocs

        if ($type == 'payroll') {
            $id_software = $company->id_software_payroll;
            $label = 'nómina';
        } elseif ($type == 'eqdocs') {
            $id_software = $company->id_software_eqdocs;
            $label = 'documentos equivalentes';
        } else {
            $id_software = $company->id_software;
            $label = 'facturación';
        }

        if (empty($id_software)) {
            return [
                'message' => "No hay ID de software configurado para {$label}.",
                'success' => false,
            ];
        }

        $ch = curl_init("{$base_url}ubl2.1/numbering-range");
        $data = [
            "Nit" => $company->identification_number,
            "IDSoftware" => $id_software,
        ];
        $data_production = json_encode($data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, ($data_production));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
            "Authorization: Bearer {$company->api_token}"
        ));
        $response_query = curl_exec($ch);
        $err = curl_error($ch);
        $respuesta = json_decode($response_query, true);

        if ($err) {
            return [
                'message' => "Error en peticion Api.",
                'success' => false,
            ];
        } else {
            if (isset($respuesta['ResponseDian'])) {
                $success = true;

                // Procesar las resoluciones para el modal
                $resolutions = [];
                if (isset($respuesta['ResponseDian']['Envelope']['Body']['GetNumberingRangeResponse']['GetNumberingRangeResult']['ResponseList']['NumberRangeResponse'])) {
                    $numberRangeResponses = $respuesta['ResponseDian']['Envelope']['Body']['GetNumberingRangeResponse']['GetNumberingRangeResult']['ResponseList']['NumberRangeResponse'];

                    // Verificar si es un array de resoluciones o una sola resolución
                    if (isset($numberRangeResponses['ResolutionNumber'])) {
                        // Es una sola resolución, convertir a array
                        $numberRangeResponses = [$numberRangeResponses];
                    }

                    // Verificar que sea un array antes de iterar
                    if (is_array($numberRangeResponses)) {
                        foreach ($numberRangeResponses as $resolution) {
                            // Verificar que $resolution sea un array antes de acceder a sus propiedades
                            if (is_array($resolution) && isset($resolution['ResolutionNumber'])) {
                                $technicalKey = '';
                                if (isset($resolution['TechnicalKey'])) {
                                    if (is_array($resolution['TechnicalKey']) && isset($resolution['TechnicalKey']['_attributes']['nil'])) {
                                        $technicalKey = '';
                                    } else {
                                        $technicalKey = $resolution['TechnicalKey'];
                                    }
                                }

                                $resolutions[] = [
                                    'ResolutionNumber' => $resolution['ResolutionNumber'] ?? '',
                                    'ResolutionDate' => $resolution['ResolutionDate'] ?? '',
                                    'Prefix' => $resolution['Prefix'] ?? '',
                                    'FromNumber' => $resolution['FromNumber'] ?? '',
                                    'ToNumber' => $resolution['ToNumber'] ?? '',
                                    'ValidDateFrom' => $resolution['ValidDateFrom'] ?? '',
                                    'ValidDateTo' => $resolution['ValidDateTo'] ?? '',
                                    'TechnicalKey' => $technicalKey ?: null,
                                    'type' => $type
                                ];
                            }
                        }
                    }
                }

                return [
                    'message' => "Consulta generada con éxito",
                    'success' => $success,
                    'resolutions' => $resolutions,
                    'ResponseDian' => $respuesta['ResponseDian']
                ];
            } else {
                return [
                    'message' => "Error en validacion de datos Api." . " - " . ($respuesta['message'] ?? 'Error desconocido'),
                    'success' => false,
                ];
            }
        }
    }

    public function storeServiceSoftware(ConfigurationServiceSoftwareCompanyRequest $request)
    {
        $company = ServiceCompany::firstOrFail();
        // $base_url = env("SERVICE_FACT", "");
        $base_url = config('tenant.service_fact');

        $ch = curl_init("{$base_url}ubl2.1/config/software");
        $data = [
            "id" => $request->id_software,
            "pin" => $request->pin_software,
        ];
        $data_software = json_encode($data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, ($data_software));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
            "Authorization: Bearer {$company->api_token}"
        ));
        $response_software = curl_exec($ch);
        $err = curl_error($ch);
        $respuesta = json_decode($response_software);

        if ($err) {
            return [
                'message' => "Error en peticion Api.",
                'success' => false,
                'software' => ''
            ];
        } else {
            if (property_exists($respuesta, 'success')) {
                $company->response_software = $response_software;
                $company->id_software = $request->id_software;
                $company->pin_software = $request->pin_software;
                $company->test_id = $request->test_id;
                $company->save();
                return [
                    'message' => "Se guardaron los cambios.",
                    'success' => true,
                    'software' => $response_software
                ];
            } else {
                return [
                    'message' => "Error en validacion de datos Api.",
                    'success' => false,
                    'software' => ''
                ];
            }
        }
    }

    public function storeServiceCertificate(ConfigurationServiceCertificateCompanyRequest $request)
    {
        $company = ServiceCompany::firstOrFail();
        // $base_url = env("SERVICE_FACT", "");

        $base_url = config("tenant.service_fact", "");

        $ch2 = curl_init("{$base_url}ubl2.1/config/certificate");
        $data = [
            "certificate" => $request->certificate64,
            "password" =>  $request->password_certificate //"Nqx4FAZ6kD"//$request->password
        ];
        $data_certificate = json_encode($data);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch2, CURLOPT_POSTFIELDS, ($data_certificate));
        curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch2, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
            "Authorization: Bearer {$company->api_token}"
        ));
        $response_certificate = curl_exec($ch2);
        $err = curl_error($ch2);
        $respuesta = json_decode($response_certificate);
        if ($err) {
            return [
                'message' => "Error en peticion Api.",
                'success' => false,
                'certificate' => ''
            ];
        } else {

            if (property_exists($respuesta, 'success')) {
                $company->response_certificate = $response_certificate;
                $company->save();
                return [
                    'message' => "Se guardaron los cambios.",
                    'success' => true,
                    'certificate' => $response_certificate
                ];
            } else {

                return [
                    'message' => "Error en validacion de datos Api.",
                    'success' => false,
                    'respuesta' => $respuesta,
                    'data' => $data_certificate
                ];
            }
        }
    }

    public function changeEnvironment($ambiente)
    {
        $company = ServiceCompany::firstOrFail();
        $base_url = config("tenant.service_fact", "");
//        $base_url = env("SERVICE_FACT", "");

        $ch2 = curl_init("{$base_url}ubl2.1/config/environment");
        if ($ambiente == 'HABILITACION')
            $data = [
                "type_environment_id" => 2,
            ];
        else
            $data = [
                "type_environment_id" => 1,
            ];

        $data_environment = json_encode($data);

        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch2, CURLOPT_POSTFIELDS, ($data_environment));
        curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch2, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
            "Authorization: Bearer {$company->api_token}"
        ));

        $response_environment = curl_exec($ch2);
        $err = curl_error($ch2);
        $respuesta = json_decode($response_environment);

        if ($err) {
            return [
                'message' => "Error en peticion Api.",
                'success' => false,
            ];
        } else {
            if (property_exists($respuesta, 'company')) {
                if ($ambiente == 'HABILITACION')
                    $company->type_environment_id = 2;
                else
                    $company->type_environment_id = 1;
                $company->update();
                return [
                    'message' => "Se guardaron los cambios.",
                    'success' => true,
                ];
            } else {
                return [
                    'message' => "Error en validacion de datos Api.",
                    'success' => false,
                    'respuesta' => $respuesta,
                    'data' => $data_environment
                ];
            }
        }
    }

    public function storeServiceResolution(ConfigurationServiceResolutionCompanyRequest $request)
    {
        if (!$request->has('prefix') || 
            is_null($request->prefix) || 
            trim($request->prefix) === '') {
            return response()->json([
                'success' => false,
                'message' => 'El prefijo es obligatorio',
                'errors' => ['prefix' => ['El prefijo es obligatorio']]
            ], 422);
        }
        try{
            $company = ServiceCompany::firstOrFail();
            $base_url = config("tenant.service_fact", "");

            $ch3 = curl_init("{$base_url}ubl2.1/config/resolution");
            $data = [
                "delete_all_type_resolutions" => false,
                "type_document_id"=> $request->type_document_id,
                "prefix"=> $request->prefix,
                "resolution"=> $request->resolution,
                "resolution_date"=> $request->resolution_date,
                "technical_key"=> $request->technical_key,
                "from"=> $request->from,
                "to"=> $request->to,
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
            ];
            $data_resolution = json_encode($data);
            curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch3, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch3, CURLOPT_POSTFIELDS,($data_resolution));
            curl_setopt($ch3, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch3, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch3, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Accept: application/json',
                "Authorization: Bearer {$company->api_token}"
            ));

            $response_resolution = curl_exec($ch3);
//            // \Log::debug($response_resolution);
            $err = curl_error($ch3);
            curl_close($ch3);
            $respuesta = json_decode($response_resolution);

            //return json_encode($respuesta);

            if($err)
            {
                return [
                    'message' => "Error en peticion Api Resolution.",
                    'success' => false,
                    'resolution' => ''
                ];
            }


            if(property_exists($respuesta, 'success'))
            {
                $company->response_resolution = $response_resolution;
                $company->save();

                TypeDocument::updateOrCreate([
                    'code' => $request->code,
                    'prefix' => $request->prefix,
                    'resolution_number' => $request->resolution,
                ], [
                    'resolution_date' => $request->date_from,
                    'resolution_date_end' => $request->date_to,
                    'technical_key' => $request->technical_key,
                    'from' => $request->from,
                    'to' => $request->to,
                    'name' => $request->name,
                    'template' => 'face_f   ',
                    'description' => $request->description
                ]);

                $response_redit_debit =  $this->storeResolutionNote();

                /*if ($request->prefix == 'SETP')
                    $this->changeEnvironment('HABILITACION');
                else
                    $this->changeEnvironment('PRODUCCION');*/

                return [
                    'message' => "Se guardaron los cambios.",
                    'success' => true,
                    'resolution' => $response_resolution,
                    'response_redit_debit' => $response_redit_debit
                ];
        }
        else{
            return [
                'message' => "Error en validacion de datos Api.",
                'success' => false,
                'resolution' => $response_resolution
            ];
        }
        }
        catch(\Exception $e)
        {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }


    //verifica si la configracion esta completa, ejecuta el test : 60 facturas,  20 notas credito, 20 notas debito
    public function testApiDian()
    {
        $company = ServiceTenantCompany::firstOrFail();
        $base_url = config("tenant.service_fact", "");
        //        $base_url = env("SERVICE_FACT", "");
        $id_test = $company->test_id;

        //envio 60 facturas
        $json_invoice = '{"number":994688605,"type_document_id":1,"customer":{"identification_number":"323232323","name":"peres","phone":"3232323","address":"sdsdsdsdsd","email":"peres@mail.com","merchant_registration":"No tiene"},"tax_totals":[{"tax_id":1,"percent":"19.00","tax_amount":"57000.00","taxable_amount":"300000.00"}],"legal_monetary_totals":{"line_extension_amount":"300000.00","tax_exclusive_amount":"300000.00","tax_inclusive_amount":"357000.00","allowance_total_amount":"0.00","charge_total_amount":"0.00","payable_amount":"357000.00"},"invoice_lines":[{"unit_measure_id":642,"invoiced_quantity":"1","line_extension_amount":"300000.00","free_of_charge_indicator":false,"tax_totals":[{"tax_id":1,"tax_amount":"57000.00","taxable_amount":"300000.00","percent":"19.00"}],"description":"POLO","code":"2323","type_item_identification_id":3,"price_amount":"13.09","base_quantity":"1.000000"}]}';
        $response_invoice = array();
        // for ($i=1; $i <=60 ; $i++) {
        $ch = curl_init("{$base_url}ubl2.1/invoice/{$id_test}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, ($json_invoice));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
            "Authorization: Bearer {$company->api_token}"
        ));

        $response = curl_exec($ch);
        array_push($response_invoice, $response);


        return [
            '60_times_invoice' =>  $response_invoice,
            //'60_times_credit_note' =>  $response_credit_note,
            //'60_times_debit_note' =>  $response_debit_note,
        ];
    }

    public function storeResolutionNote()
    {

        $response = [];

        DB::connection('tenant')->beginTransaction();
        try {
            $company = ServiceCompany::firstOrFail();
            $base_url = config("tenant.service_fact", "");
            //NOTA CREDITO
            $ch5 = curl_init("{$base_url}ubl2.1/config/resolution");
            $data_c = [
                "type_document_id" => 4,
                "from" => 1,
                "to" => 99999999,
                "prefix" => "NC",
            ];

            $data_resolution = json_encode($data_c);
            curl_setopt($ch5, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch5, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch5, CURLOPT_POSTFIELDS, ($data_resolution));
            curl_setopt($ch5, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch5, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch5, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Accept: application/json',
                "Authorization: Bearer {$company->api_token}"
            ));

            $response_credit = curl_exec($ch5);
            $response["credit"] = $response_credit;
            curl_close($ch5);
            $company->response_resolution_credit = $response_credit;

            TypeDocument::updateOrCreate([
                'code' => 4
            ], [
                'resolution_date' => NULL,
                'resolution_date_end' => NULL,
                'prefix' => "NC",
                'from' => 1,
                'to' => 99999999
            ]);

            //NOTA DEBITO
            $ch4 = curl_init("{$base_url}ubl2.1/config/resolution");
            $data_d = [
                "type_document_id" => 5,
                "from" => 1,
                "to" => 99999999,
                "prefix" => "ND",
            ];
            $data_resolution_de = json_encode($data_d);
            curl_setopt($ch4, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch4, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch4, CURLOPT_POSTFIELDS, ($data_resolution_de));
            curl_setopt($ch4, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch4, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch4, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Accept: application/json',
                "Authorization: Bearer {$company->api_token}"
            ));

            TypeDocument::updateOrCreate([
                'code' => 5
            ], [
                'resolution_date' => NULL,
                'resolution_date_end' => NULL,
                'prefix' => "ND",
                'from' => 1,
                'to' => 99999999
            ]);

            $response_debit = curl_exec($ch4);
            $response["debit"] = $response_debit;

            curl_close($ch4);
            $company->response_resolution_debit = $response_debit;
            $company->save();
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => $response
            ];
        }

        DB::connection('tenant')->commit();

        return [
            'success' => true,
            'message' => "Se registraron con éxito las resoluciones para notas contables.",
            'data' => $response
        ];
    }

    public function co_type_documents(Request $request)
    {
        $module = $request->get('module');

        if (!$module) {
            return [
                'data' => TypeDocument::whereNotNull('resolution_number')->whereIn('code', [1, 2, 3])->get()
            ];
        }

        $documentCodes = [
            'invoice' => [1, 4, 5, 11, 13],
            'eqdocs' => [15, 26],
        ];

        $codes = $documentCodes[$module] ?? $documentCodes['invoice'];

        return [
            'data' => TypeDocument::whereNotNull('resolution_number')
                ->whereIn('code', $codes)
                ->get()
        ];
    }

    public function destroy($resolution)
    {
        $it = TypeDocument::find($resolution);
        $it->delete();

        return [
            'success' => true,
            'message' => "Se elimino con éxito el registro {$it->prefix} / {$it->resolution_number}."
        ];
    }


    /**
     * Regitrar id y pin sw para nomina en api
     *
     * @param  mixed $request
     * @return array
     */
    public function storeServiceSoftwarePayroll(ConfigurationServiceSoftwarePayrollRequest $request)
    {
        $company = ServiceCompany::firstOrFail();
        $connection_api = (new HttpConnectionApi($company->api_token));
        $send_request_to_api = $connection_api->sendRequestToApi('ubl2.1/config/softwarepayroll', $request->all());
        $response_message = isset($send_request_to_api['message']) ? $send_request_to_api['message'] : null;

        // dd($send_request_to_api, isset($send_request_to_api['success']));

        if (isset($send_request_to_api['success'])) {
            if ($send_request_to_api['success']) {

                $this->updateServiceCompany($company, $request);
                return $connection_api->responseMessage(true, $response_message);
            } else {
                return $connection_api->responseMessage(false, 'Error en validacion de datos Api.' . ($response_message ? ' - ' . $response_message : ''));
            }
        }
        return $connection_api->responseMessage(false,  'Error en peticion Api.' . ($response_message ? ' - ' . $response_message : ''));
    }

    /**
     * Regitrar id y pin sw para documentos equivalentes en api
     *
     * @param  mixed $request
     * @return array
     */
    public function storeServiceSoftwareEqDocs(ConfigurationServiceSoftwareEqDocsRequest $request)
    {
        $company = ServiceCompany::firstOrFail();
        $connection_api = (new HttpConnectionApi($company->api_token));
        $send_request_to_api = $connection_api->sendRequestToApi('ubl2.1/config/softwareeqdocs', $request->all());
        $response_message = isset($send_request_to_api['message']) ? $send_request_to_api['message'] : null;

        if (isset($send_request_to_api['success'])) {
            if ($send_request_to_api['success']) {
                $this->updateServiceCompany($company, $request);
                return $connection_api->responseMessage(true, $response_message);
            } else {
                return $connection_api->responseMessage(false, 'Error en validacion de datos Api.' . ($response_message ? ' - ' . $response_message : ''));
            }
        }
        return $connection_api->responseMessage(false,  'Error en peticion Api.' . ($response_message ? ' - ' . $response_message : ''));
    }

    private function updateServiceCompany($company, $request)
    {
        $company->test_set_id_payroll = isset($request->test_set_id_payroll) ? $request->test_set_id_payroll : $company->test_set_id_payroll;
        $company->pin_software_payroll = isset($request->pinpayroll) ? $request->pinpayroll : $company->pin_software_payroll;
        $company->id_software_payroll = isset($request->idpayroll) ? $request->idpayroll : $company->id_software_payroll;
        $company->test_set_id_eqdocs = isset($request->test_set_id_eqdocs) ? $request->test_set_id_eqdocs : $company->test_set_id_eqdocs;
        $company->pin_software_eqdocs = isset($request->pineqdocs) ? $request->pineqdocs : $company->pin_software_eqdocs;
        $company->id_software_eqdocs = isset($request->ideqdocs) ? $request->ideqdocs : $company->id_software_eqdocs;
        $company->save();
    }

    public function storeResolutionFromModal(Request $request)
    {
        try {
            // Validar datos básicos
            $request->validate([
                'type_document_id' => 'required|integer',
                'resolution_number' => 'required|string',
                'prefix' => 'required|string|max:4',
                'resolution_date' => 'required|date',
                'resolution_date_end' => 'required|date',
                'technical_key' => 'required|string',
                'from' => 'required|integer',
                'to' => 'required|integer',
                'module_type' => 'string|in:invoice,eqdocs'
            ]);

            $company = ServiceCompany::firstOrFail();
            $base_url = config("tenant.service_fact", "");

            // Obtener el tipo de documento
            $typeDoc = $this->getTypeDocumentById($request->type_document_id);

            if (!$typeDoc) {
                return [
                    'message' => 'Tipo de documento no válido.',
                    'success' => false,
                ];
            }

            // Preparar datos para la API
            $apiData = [
                "delete_all_type_resolutions" => false,
                "type_document_id" => $typeDoc['code'],
                "prefix" => strtoupper($request->prefix),
                "resolution" => $request->resolution_number,
                "resolution_date" => $request->resolution_date,
                "technical_key" => $request->technical_key,
                "from" => $request->from,
                "to" => $request->to,
                'date_from' => $request->resolution_date,
                'date_to' => $request->resolution_date_end,
            ];

            // Usar el mismo endpoint para ambos tipos de documentos
            $ch = curl_init("{$base_url}ubl2.1/config/resolution");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($apiData));
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
                "Authorization: Bearer {$company->api_token}"
            ]);

            $response = curl_exec($ch);
            $err = curl_error($ch);
            curl_close($ch);

            if ($err) {
                return [
                    'message' => "Error en petición API: {$err}",
                    'success' => false,
                ];
            }

            $result = json_decode($response, true);

            if (isset($result['success']) && $result['success']) {
                // Guardar en la base de datos local
                TypeDocument::updateOrCreate([
                    'code' => $typeDoc['code'],
                    'prefix' => strtoupper($request->prefix),
                    'resolution_number' => $request->resolution_number,
                ], [
                    'resolution_date' => $request->resolution_date,
                    'resolution_date_end' => $request->resolution_date_end,
                    'technical_key' => $request->technical_key,
                    'from' => $request->from,
                    'to' => $request->to,
                    'generated' => $request->generated ?? 0,
                    'name' => $typeDoc['name'],
                    'template' => 'face_f',
                    'description' => $request->description ?? "Resolución {$request->resolution_number}",
                    'show_in_establishments' => $request->show_in_establishments ?? 'all',
                    'establishment_ids' => $request->establishment_ids ?? null,
                ]);

                return [
                    'message' => "Resolución creada exitosamente.",
                    'success' => true,
                ];
            } else {
                return [
                    'message' => "Error en validación de datos API.",
                    'success' => false,
                    'response' => $response
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    private function getTypeDocumentById($id)
    {
        $typeDocuments = [
            1 => ['id' => 1, 'name' => "Factura de Venta Nacional", 'code' => '1'],
            4 => ['id' => 4, 'name' => "Nota Crédito", 'code' => '4'],
            5 => ['id' => 5, 'name' => "Nota Débito", 'code' => '5'],
            11 => ['id' => 11, 'name' => "Documento Soporte Electrónico", 'code' => '11'],
            13 => ['id' => 13, 'name' => "Nota de Ajuste al Documento Soporte Electrónico", 'code' => '13'],
            15 => ['id' => 15, 'name' => "Documento Equivalente", 'code' => '15'],
            26 => ['id' => 26, 'name' => "Nota de crédito al Documento Equivalente", 'code' => '26']
        ];
        
        return $typeDocuments[$id] ?? null;
    }
}
