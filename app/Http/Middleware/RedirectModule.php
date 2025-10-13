<?php

namespace App\Http\Middleware;

use Closure;

class RedirectModule
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
      
        $module = $request->user()->getModule();
        $path = explode('/', $request->path());
        // $modules = $request->user()->getModules();
        $modulesCollection = $request->user()->getModules(); // colección
        $modules = $modulesCollection->pluck('value')->toArray();
        // Determina el grupo de la ruta actual
        $group = $this->getGroup($path, $module);

        if(! $request->ajax()){

            // if(count($modules)){

            //     if(count($modules) < 9){

            //         $group = $this->getGroup($path, $module);
            //         if($group){
            //             if($this->getModuleByGroup($modules,$group) === 0){
            //                 return $this->redirectRoute($module);
            //             }

            //         }
            //     }

            // }

            // Validación especial para invoicehealth
            if ($group === 'invoicehealth' && (!in_array('invoicehealth', $modules) || !in_array('documents', $modules))) {
                return $this->redirectRoute(null, $modules);
            }
            if ($group && !in_array($group, $modules)) {
                return $this->redirectRoute(null, $modules);
            }
        }

        return $next($request);

    }


    private function redirectRoute($module = null, $modules = []){

        // switch ($module) {

        //     case 'pos':
        //         return redirect()->route('tenant.pos.index');

        //     case 'documents':
        //         return redirect()->route('tenant.documents.create');

        //     case 'purchases':
        //         return redirect()->route('tenant.purchases.index');

        //     case 'advanced':
        //         return redirect()->route('tenant.retentions.index');

        //     case 'reports':
        //         return redirect()->route('tenant.reports.purchases.index');

        //     case 'configuration':
        //         return redirect()->route('tenant.companies.create');

        //     case 'inventory':
        //         return redirect()->route('warehouses.index');

        //     case 'accounting':
        //         return redirect()->route('tenant.account.index');

        //     case 'finance':
        //         return redirect()->route('tenant.finances.global_payments.index');
                
        //     /*case 'ecommerce':
        //         return redirect()->route('tenant.ecommerce.index');*/

        // }
        $routes = [
            'pos'            => 'tenant.pos.index',
            'documents'      => 'tenant.documents.create',
            'purchases'      => 'tenant.purchases.index',
            'advanced'       => 'tenant.retentions.index',
            'reports'        => 'tenant.reports.purchases.index',
            'configuration'  => 'tenant.configuration.documents',
            'inventory'      => 'warehouses.index',
            'accounting'     => 'tenant.accounting.journal.entries.index',
            'finance'        => 'tenant.finances.global_payments.index',
            'dashboard'      => 'tenant.dashboard.index',
            'ecommerce'      => 'tenant.ecommerce.index',
            'payroll'        => 'tenant.payroll.document-payrolls.index',
            'radian'         => 'tenant.co-radian-events-manage.index',
            'invoicehealth'  => 'tenant.co-documents-health.create',
            'contacts'       => 'tenant.persons.index',      // Nuevo módulo Contactos
            'products'       => 'tenant.items.index',
        ];

        // Busca el primer módulo activo que tenga ruta
        foreach ($modules as $activeModule) {
            if ($activeModule === 'invoicehealth' && !in_array('documents', $modules)) {
                continue;
            }
            if (isset($routes[$activeModule])) {
                if ($activeModule === 'contacts') {
                    return redirect()->route($routes[$activeModule], ['type' => 'customers']); // Por defecto, redirige a 'customers'
                }
                return redirect()->route($routes[$activeModule]);
            }
        }

        abort(403, 'No tiene módulos activos.');
    }



    private function getModuleByGroup($modules,$group){

        $modules_x_group  = $modules->filter(function ($module, $key) use($group){
            return $module->value === $group;
        });

        return $modules_x_group->count();
    }


    private function getGroup($path, $module){

        if($path[0] == "documents" || $path[0] == "quotations" || $path[0] == "summaries" || $path[0] == "voided"|| $path[0] == "co-documents" ) {
            return "documents";
        }
        if($path[0] == "items" || $path[0] == "categories" || $path[0] == "brands") {
            return "products";
        }
        if($path[0] == "dashboard") {
            return "dashboard";
        }
        if($path[0] == "purchases" || $path[0] == "expenses") {
            return "purchases";
        }
        if($path[0] == "retentions" || $path[0] == "dispatches" || $path[0] == "perceptions") {
            return "advanced";
        }
        if($path[0] == "reports") {
            return "reports";
        }
        if(in_array($path[0], ["users", "establishments", "catalogs", "advanced"])) {
            return "configuration";
        }
        if($path[0] == "companies") {
            if(count($path) > 1 && $path[1] == "uploads" && $module == "documents") {
                return "configuration";
            }
            return "configuration";
        }
        if($path[0] == "persons") {
            return "contacts";
        }
        if(in_array($path[0], ["pos", "cash"])) {
            return "pos";
        }
        if(in_array($path[0], ["warehouses", "inventory"])) {
            return "inventory";
        }
        if($path[0] == "accounting") {
            return "accounting";
        }
        if($path[0] == "finances") {
            return "finance";
        }
        if($path[0] == "payroll") {
            return "payroll";
        }
        if($path[0] == "invoicehealth" || $path[0] == "co-documents-health") {
            return "invoicehealth";
        }
        if($path[0] == "ecommerce") {
            return "ecommerce";
        }
        if($path[0] == "radian" || $path[0] == "co-radian-events") {
            return "radian";
        }
        if(
            $path[0] == "configuration" ||
            $path[0] == "companies" ||
            $path[0] == "co-configuration" ||
            $path[0] == "co-configuration-documents" ||
            in_array($path[0], ["users", "establishments", "catalogs", "advanced"])
        ) {
            return "configuration";
        }
        return null;
    }

}
