<?php

namespace Modules\Factcolombia1\Http\Controllers\System;

use Modules\Factcolombia1\Jobs\Tenant\ConfigureTenantJob;
use Hyn\Tenancy\Contracts\Repositories\{
    HostnameRepository,
    WebsiteRepository
};
use Modules\Factcolombia1\Http\Controllers\Controller;
use Modules\Factcolombia1\Http\Requests\System\{
    CompanyUpdateRequest,
    CompanyRequest
};
use Modules\Factcolombia1\Models\System\Company;
use Illuminate\Http\Request;
use Hyn\Tenancy\Environment;
use Hyn\Tenancy\Models\{
    Hostname,
    Website
};
use Modules\Factcolombia1\Models\Tenant\{
    Company as TenantCompany,
    User
};

use Modules\Factcolombia1\Models\TenantService\{
    Company as TenantServiceCompany
};
use Carbon\Carbon;
use DB;

use Modules\Factcolombia1\Models\SystemService\{
    Country as ServiceCountry,
   // Department as ServiceDepartment,
    Language as ServiceLanguage,
    Tax as ServiceTax,
    TypeEnvironment as ServiceTypeEnvironment,
    TypeOperation as ServiceTypeOperation,
    TypeDocumentIdentification as ServiceTypeDocumentIdentification,
    TypeCurrency as ServiceTypeCurrency,
    TypeOrganization as ServiceTypeOrganization,
    TypeRegime as ServiceTypeRegime,
    TypeLiability as ServiceTypeLiability,
    Department as ServiceDepartment,
    Municipality as ServiceMunicipality,
    Company as ServiceCompany
};

use App\Models\System\Module;
use App\Models\System\Plan;
use Modules\Factcolombia1\Traits\System\CompanyTrait;
use Exception;
use Modules\Factcolombia1\Http\Resources\System\{
    CompanyCollection,
    CompanyResource
};


class CompanyController extends Controller
{
    use CompanyTrait;

    public function store(CompanyRequest $request) {

        $limite = config('app.limite_reseller');
        $totalEmpresas = Company::count();
        if ($totalEmpresas >= $limite) {
            return [
                'success' => false,
                'message' => 'Has alcanzado el límite de empresas permitidas.'
            ];
        }

        $response = $this->createCompanyApiDian($request);
        if(!is_object($response) || !property_exists( $response, 'password' ) || !property_exists( $response, 'token' )){
            return [
                'message' => "Error al registrar Compañía en ApiDian",
                'response' => $response,
                'success' => false
            ];
        }
        $request->api_token = $response->token;

        DB::connection('system')->beginTransaction();

        try {
            $subDom = strtolower($request->input('subdomain'));
            $uuid = config('tenant.prefix_database').'_'.$subDom;
            $fqdn = $subDom.'.'.config('tenant.app_url_base');

            // Website
            $website = new Website;
            $website->uuid = $uuid;

            $this->validateWebsite($uuid, $website);

            app(WebsiteRepository::class)->create($website);

            // Hostname
            $hostname = new Hostname;
            $hostname->fqdn = $fqdn;
            $hostname = app(HostnameRepository::class)->create($hostname);

            app(HostnameRepository::class)->attach($hostname, $website);

            $company = $this->createSystemCompany($request, $hostname);

            $plan = Plan::find($company->plan_id);
            if ($plan) {
                $now = Carbon::now();
                $company->plan_started_at = $now;
                $company->plan_expires_at = $plan->period === 'year'
                    ? $now->copy()->addYear()
                    : $now->copy()->addMonth();
                $company->plan_active = true;
                $company->plan_auto_renew = false;
                $company->locked_emission = true;
                $company->locked_users = true;
                $company->save();
            }

            // Switch
            $tenancy = app(Environment::class);
            $tenancy->tenant($website);

            DB::connection('tenant')->beginTransaction();

        }
        catch (Exception $e) {

            DB::connection('system')->rollBack();

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];

        }

        try {

            $this->runTenantPeruSeeder($request);
            $this->runTenantSeeder($request, $response, $company);


            DB::connection('system')->commit();
            DB::connection('tenant')->commit();

        }
        catch (Exception $e) {

            DB::connection('system')->rollBack();
            DB::connection('tenant')->rollBack();

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];

        }

        // Switch
        $tenancy = app(Environment::class);
        $tenancy->tenant(app(\Hyn\Tenancy\Environment::class)->website());

        config(['database.default' => 'system']);

        //dispatch((new ConfigureTenantJob)->onTenant($website->id)); ya no estara en cola

        return [
            'message' => "Se registro con éxito la compañía {$company->name}.",
            'company' => $company,
            'success' => true
        ];

    }
    protected $nameclient;
    public function setNameClient($name)
    {
        $this->nameclient = $name;
    }

    public function searchName($nit)
    {
        $client = new \Goutte\Client();
        $crawler = $client->request('GET', "https://www.einforma.co/servlet/app/portal/ENTP/prod/LISTA_EMPRESAS/razonsocial/{$nit}");
        $crawler->filter('h1[class="title01"]')->each(function($node) {
            $text = $node->text();
            $marker = 'Situación de la empresa:';
            if (strpos($text, $marker) !== false) {
                $name = substr($text, 0, strpos($text, $marker));
            } else {
                $name = $text;
            }
            $name = trim($name);
            $this->setNameClient($name);
        });

        return [
            'name' => $this->nameclient
        ];
    }

    public function validateWebsite($uuid, $website){

        $exists = $website::where('uuid', $uuid)->first();

        if($exists){
            throw new Exception("El subdominio ya se encuentra registrado");
        }

    }


    public function records()
    {

        $records = Company::latest()->get();

        foreach ($records as &$row) {
            $tenancy = app(Environment::class);
            $tenancy->tenant($row->hostname->website);
            // $row->count_doc = DB::connection('tenant')->table('documents')->count();
            $row->count_doc = DB::connection('tenant')->table('configurations')->first()->quantity_documents;
            //$row->count_user = DB::connection('tenant')->table('users')->count();

            if($row->start_billing_cycle)
            {
                $day_start_billing = date_format($row->start_billing_cycle, 'j');
                $day_now = (int)date('j');


                if( $day_now <= $day_start_billing  )
                {
                    $init = Carbon::parse( date('Y').'-'.((int)date('n') -1).'-'.$day_start_billing );
                    $end = Carbon::parse(date('Y-m-d'));

                    $row->count_doc_month = DB::connection('tenant')->table('documents')->whereBetween('date_of_issue', [ $init, $end  ])->count();
                }
                else{

                    $init = Carbon::parse( date('Y').'-'.((int)date('n') ).'-'.$day_start_billing );
                    $end = Carbon::parse(date('Y-m-d'));
                    $row->count_doc_month = DB::connection('tenant')->table('documents')->whereBetween('date_of_issue', [ $init, $end  ])->count();

                }

            }
        }

        return new CompanyCollection($records);
    }


    public function record($id)
    {
        $company = Company::findOrFail($id);
        $tenancy = app(Environment::class);
        $tenancy->tenant($company->hostname->website);
        $company->modules = DB::connection('tenant')->table('module_user')->where('user_id', 1)->get();
        return new CompanyResource($company);
    }


    /**
     * All
     * @return \Illuminate\Http\Response
     */
    public function all() {
        return  [
                    'company' =>  Company::all(),
                    'servicecompany' => ServiceCompany::all()
                ];
    }

    /**
     * Update
     * @param  \App\Models\System\Company $company
     * @param  \App\Http\Requests\System\CompanyUpdateRequest $request
     * @return \Illuminate\Http\Response
     */
    public function update(CompanyUpdateRequest $request) {
        $response = $this->createCompanyApiDian($request);

        if(!is_object($response) || !property_exists( $response, 'password' ) || !property_exists( $response, 'token' )){
            return [
                'message' => "Error al actualizar compañía en ApiDian",
                'response' => $response,
                'success' => false
            ];
        }

        $company = Company::findOrFail($request->id);
        $old_plan_id = $company->plan_id;

        $company->update([
            'limit_documents' => $request->limit_documents,
            'limit_users' => $request->limit_users,
            'economic_activity_code' => $request->economic_activity_code,
            'ica_rate' => $request->ica_rate,
            'plan_id' => $request->plan_id,
        ]);
        
        // Si se cambia el plan, actualiza fechas
        if ($request->plan_id && $old_plan_id != $request->plan_id) {
            $plan = Plan::find($request->plan_id);
            if ($plan) {
                $now = Carbon::now();
                $company->plan_started_at = $now;
                $company->plan_expires_at = $plan->period === 'year'
                    ? $now->copy()->addYear()
                    : $now->copy()->addMonth();
                $company->plan_active = true;
                $company->save();
            }
        }

        $tenancy = app(Environment::class);
        $tenancy->tenant($company->hostname->website);
        DB::connection('tenant')->table('configurations')->where('id', 1)->update(['limit_users' => $company->limit_users]);

        ServiceCompany::where('identification_number', $company->identification_number)->first()
            ->update(
                [
                    'type_document_identification_id' => $request->type_document_identification_id,
                    'department_id' => $request->department_id,
                    'type_organization_id' => $request->type_organization_id,
                    'type_regime_id' => $request->type_regime_id,
                    'municipality_id' => $request->municipality_id,
                    'merchant_registration' => $request->merchant_registration,
                    'address' => $request->address,
                    'phone' => $request->phone,
                    'type_liability_id' => $request->type_liability_id,
                    'api_token' => $response->token,
                ]
            );


        app(Environment::class)
            ->tenant($company->hostname->website);

        TenantCompany::firstOrFail()
            ->update([
                'limit_documents' => $request->limit_documents,
                'economic_activity_code' => $request->economic_activity_code,
                'ica_rate' => $request->ica_rate
            ]);

        if ($request->password != null) {
            User::firstOrFail()
                ->update([
                    'password' => bcrypt($request->password),
                ]);
        }

        TenantServiceCompany::firstOrFail()
            ->update(
                [
                    'type_document_identification_id' => $request->type_document_identification_id,
                    'department_id' => $request->department_id,
                    'type_organization_id' => $request->type_organization_id,
                    'type_regime_id' => $request->type_regime_id,
                    'municipality_id' => $request->municipality_id,
                    'merchant_registration' => $request->merchant_registration,
                    'address' => $request->address,
                    'phone' => $request->phone,
                    'type_liability_id' => $request->type_liability_id,
                ]
            );

        //modules
        DB::connection('tenant')->table('module_user')->where('user_id', 1)->delete();
        DB::connection('tenant')->table('module_level_user')->where('user_id', 1)->delete();

        $array_modules = [];

        foreach ($request->modules as $module) {
            if($module['checked']){
                $array_modules[] = ['module_id' => $module['id'], 'user_id' => 1];

                if($module['id'] == 1){
                    DB::connection('tenant')->table('module_level_user')->insert([
                        ['module_level_id' => 1, 'user_id' => 1],
                        ['module_level_id' => 2, 'user_id' => 1],
                        // ['module_level_id' => 3, 'user_id' => 1],
                        // ['module_level_id' => 4, 'user_id' => 1],
                        ['module_level_id' => 5, 'user_id' => 1],
                        // ['module_level_id' => 6, 'user_id' => 1],
                        ['module_level_id' => 7, 'user_id' => 1],
                        ['module_level_id' => 8, 'user_id' => 1],
                        ['module_level_id' => 9, 'user_id' => 1],
                    ]);
                }
            }
        }

        DB::connection('tenant')->table('module_user')->insert($array_modules);
        //modules

        return [
            'message' => "Se actualizo con éxito la compañía {$company->name}.",
            'success' => true
        ];
    }

    public function changePassword(Request $request, $id)
    {
        $company = Company::findOrFail($id);
        $tenancy = app(Environment::class);
        $tenancy->tenant($company->hostname->website);

        $user = \Modules\Factcolombia1\Models\Tenant\User::firstOrFail();
        $user->password = bcrypt($request->password);
        $user->save();

        return [
            'success' => true,
            'message' => 'Contraseña actualizada correctamente.'
        ];
    }

    public function renewPlan(Request $request, $id)
    {
        $company = Company::findOrFail($id);
        $plan = Plan::findOrFail($company->plan_id);

        $now = Carbon::now();
        $company->plan_started_at = $now;
        $company->plan_expires_at = $plan->period === 'year'
            ? $now->copy()->addYear()
            : $now->copy()->addMonth();
        $company->plan_active = true;
        $company->save();

        return [
            'success' => true,
            'message' => 'Plan renovado correctamente',
            'plan_expires_at' => $company->plan_expires_at->toDateString(),
        ];
    }

    public function toggleAutoRenew(Request $request, $id)
    {
        $company = Company::findOrFail($id);
        $company->plan_auto_renew = !$company->plan_auto_renew;
        $company->save();

        return [
            'success' => true,
            'plan_auto_renew' => $company->plan_auto_renew,
        ];
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Company $company) {

        $hostname = Hostname::findOrFail($company->hostname_id);
        $website = Website::findOrFail($hostname->website_id);

        app(HostnameRepository::class)
            ->delete($hostname, true);

        app(WebsiteRepository::class)
            ->delete($website, true);

        DB::table('co_service_companies')->where('identification_number', $company->identification_number)->delete();
        Company::destroy($company->id);

        $this->deleteApi($company);

        return [
            'success' => true,
            'message' => "Se elimino la compañía {$company->name}."
        ];

    }

    public function deleteApi($company)
    {
        $base_url = config('tenant.service_fact');
        $number = $company->identification_number;
        $email = $company->email;
        $ch = curl_init("{$base_url}ubl2.1/config/delete/{$number}/{$email}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
        ));
        $response = curl_exec($ch);
        curl_close($ch);
        $respuesta = json_decode($response);
    }


    public function tables()
    {

        $id_country = 46; //colombia
        $department = ServiceDepartment::where('country_id', $id_country)->get();
        return [
            //'country' => ServiceCountry::all(),
            'departments' => $department,
            'municipalities' => ServiceMunicipality::whereIn('department_id',  $department->pluck('id'))->get(),
         //   'language' => ServiceLanguage::all(),
          //  'tax' => ServiceTax::all(),
           // 'type_enviroment' => ServiceTypeEnvironment::all(),
          //  'type_operation' => ServiceTypeOperation::all(),
            'type_document_identifications' => ServiceTypeDocumentIdentification::all(),
          //  'type_currency' => ServiceTypeCurrency::all(),
            'type_organizations' => ServiceTypeOrganization::all(),
            'type_regimes' => ServiceTypeRegime::all(),
            // 'modules' => Module::whereIn('id', [1,2,4,5,6,7,8,10,12])->orderBy('description')->get(),
            'modules' => Module::whereIn('id', auth()->user()->getAllowedModulesForSystem())->orderBy('description')->get(),
            'url_base' => '.'.config('tenant.app_url_base'),
            'type_liabilities' => ServiceTypeLiability::all()
        ];
    }


    public function cascade(Request $request)
    {
      $name = $request->name;
      $value = $request->value;
      $data = [];

      switch ($name) {
          case 'country':
              $data = ServiceDepartment::where('country_id', $value)->get();
              break;
          case 'department':
              $data = ServiceMunicipality::where('department_id', $value)->get();
              break;
      }

      return $data;
    }


    public function getInformationDocument($nit, $desde = NULL, $hasta = NULL)
    {
        $base_url = config('tenant.service_fact');
        $ch2 = curl_init("{$base_url}information/{$nit}/{$desde}/{$hasta}");

        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch2, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
        ));
        $response_data = curl_exec($ch2);
        $err = curl_error($ch2);
        curl_close($ch2);
       // $response_encode = json_decode($response_data);
        if($err){
            return [
                'success' => false,
                'message'=> 'Error en Api'
            ];
        }
        else{
            return $response_data;
        }

    }



    public function lockedUser(Request $request){

        $company = Company::findOrFail($request->id);
        $company->locked_users = $request->locked_users;
        $company->save();

        $tenancy = app(Environment::class);
        $tenancy->tenant($company->hostname->website);
        DB::connection('tenant')->table('configurations')->where('id', 1)->update(['locked_users' => $company->locked_users]);

        return [
            'success' => true,
            'message' => ($company->locked_users) ? 'Limitar creación de usuarios activado' : 'Limitar creación de usuarios desactivado'
        ];

    }


    public function lockedEmission(Request $request){

        $company = Company::findOrFail($request->id);
        $company->locked_emission = $request->locked_emission;
        $company->save();

        $tenancy = app(Environment::class);
        $tenancy->tenant($company->hostname->website);
        DB::connection('tenant')->table('configurations')->where('id', 1)->update(['locked_emission' => $company->locked_emission]);

        return [
            'success' => true,
            'message' => ($company->locked_emission) ? 'Limitar emisión de documentos activado' : 'Limitar emisión de documentos desactivado'
        ];

    }


    public function lockedTenant(Request $request){

        $company = Company::findOrFail($request->id);
        $company->locked_tenant = $request->locked_tenant;
        $company->save();

        $tenancy = app(Environment::class);
        $tenancy->tenant($company->hostname->website);
        DB::connection('tenant')->table('configurations')->where('id', 1)->update(['locked_tenant' => $company->locked_tenant]);

        return [
            'success' => true,
            'message' => ($company->locked_tenant) ? 'Cuenta bloqueada' : 'Cuenta desbloqueada'
        ];

    }

    public function startBillingCycle(Request $request)
    {
        $client = Company::findOrFail($request->id);
        $client->start_billing_cycle = $request->start_billing_cycle;
        $client->save();

        return [
            'success' => true,
            'message' => ($client->start_billing_cycle) ? 'Ciclo de Facturacion definido.' : 'No se pudieron guardar los cambios.'
        ];
    }


}
