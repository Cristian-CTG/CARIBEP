<?php

namespace Modules\Accounting\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Accounting\Models\ChartOfAccount;
use Modules\Accounting\Models\ChartAccountSaleConfiguration;
use Modules\Accounting\Models\AccountingChartAccountConfiguration;
use Modules\Accounting\Models\PayrollAccountConfiguration;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\HeadingRowImport;
use Modules\Accounting\Imports\ChartOfAccountImport;

/*
 * Clase ChartOfAccountController
 * Controlador para gestionar las cuentas contables del sistema
 */
class ChartOfAccountController extends Controller
{
    public function index(Request $request)
    {
        return view('accounting::chart_of_accounts.index');
    }

    public function columns()
    {
        return [
            'level' => 'Jerarquía',
        ];
    }

    public function records(Request $request)
    {
        $perPage = $request->input('per_page', 20); // Número de registros por página
        $page = $request->input('page', 1); // Página actual
        $column = $request->input('column', 'date'); // Columna para buscar (por defecto 'date')
        $value = $request->input('value', ''); // Valor de búsqueda

        // Construir la consulta base
        $query = ChartOfAccount::with('parent');

        // Aplicar filtro si el valor no está vacío
        if (!empty($value)) {
            $query->where($column, 'like', "%$value%");
        }

        // Ordenar por el código jerárquico
        $query->orderBy('code', 'asc');

        // Obtener datos paginados
        $entries = $query->paginate($perPage, ['*'], 'page', $page);

        // Construir respuesta con estructura específica
        return response()->json([
            "data" => $entries->items(), // Lista de registros
            "links" => [
                "first" => $entries->url(1),
                "last" => $entries->url($entries->lastPage()),
                "prev" => $entries->previousPageUrl(),
                "next" => $entries->nextPageUrl(),
            ],
            "meta" => [
                "current_page" => $entries->currentPage(),
                "from" => $entries->firstItem(),
                "last_page" => $entries->lastPage(),
                "path" => request()->url(),
                "per_page" => (string) $entries->perPage(),
                "to" => $entries->lastItem(),
                "total" => $entries->total(),
            ]
        ]);
    }


    /**
     * Create a new account
     *
     * @bodyParam code string required Code of account. Example: 1101
     * @bodyParam name string required Name of account. Example: Bank
     * @bodyParam type string required Required type of account. Example: Asset
     * @bodyParam parent_id integer nullable Optional parent account id. Example: 1
     * @bodyParam level integer required Required level of account. Example: 1
     * @bodyParam status boolean required Required status of account. Example: 1
     * @response 201 {
     *  "data": {
     *      "id": 1,
     *      "code": "1101",
     *      "name": "Bank",
     *      "type": "Asset",
     *      "parent_id": 1,
     *      "level": 1,
     *      "status": 1,
     *      "created_at": "2020-09-01 00:00:00",
     *      "updated_at": "2020-09-01 00:00:00"
     *  }
     * }
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|max:10', function ($attribute, $value, $fail) {
                // Buscar manualmente en la base de datos del tenant
                if (\DB::connection('tenant')->table('chart_of_accounts')->where('code', $value)->exists()) {
                    $fail('El código ya está en uso.');
                }
            },
            'name' => 'required|string|max:255',
            'type' => 'required|in:Asset,Liability,Equity,Revenue,Expense,Cost,ProductionCost,OrderDebit,OrderCredit',
            'parent_id' => 'nullable',
            'level' => 'required|integer|min:1|max:6'
        ]);

        $request->merge([
            'status' => 1
        ]);

        $account = ChartOfAccount::create($request->only(['code', 'name', 'type', 'parent_id', 'level', 'status']));

        return response()->json([
            'success' => true,
            'message' => 'Asiento contable creado exitosamente',
            'data' => $account
        ], 201);
    }

    /**
     * Display the specified account with its children.
     *
     * @param  int  $id  The ID of the account to display.
     * @return \Illuminate\Http\JsonResponse  The JSON response containing the account details.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException  If the account is not found.
     */

    public function show($id)
    {
        $account = ChartOfAccount::with('children')->findOrFail($id);
        // Obtener jerarquías superiores (padres)
        $parents = [];
        $currentParent = $account->parent;

        while ($currentParent) {
            $parents[] = $currentParent;
            $currentParent = $currentParent->parent; // Subir al siguiente nivel
        }

        return response()->json([
            'success' => true,
            'data' => [
                'account' => $account,
                'parents' => array_reverse($parents), // Ordenar de nivel superior a inferior
            ],
        ]);
    }

    /**
     * Update the specified account.
     *
     * @bodyParam name string Name of account. Example: Bank
     * @bodyParam type string Required type of account. Example: Asset
     * @bodyParam parent_id integer Optional parent account id. Example: 1
     * @bodyParam level integer Required level of account. Example: 1
     * @bodyParam status boolean Required status of account. Example: 1
     * @response 200 {
     *  "data": {
     *      "id": 1,
     *      "code": "1101",
     *      "name": "Bank",
     *      "type": "Asset",
     *      "parent_id": 1,
     *      "level": 1,
     *      "status": 1,
     *      "created_at": "2020-09-01 00:00:00",
     *      "updated_at": "2020-09-01 00:00:00"
     *  }
     * }
     */
    public function update(Request $request, $id)
    {
        $account = ChartOfAccount::findOrFail($id);

        $request->validate([
            'code' => 'required|max:8',
            'name' => 'required|string|max:255',
            'type' => 'required|in:Asset,Liability,Equity,Revenue,Expense,Cost,ProductionCost,OrderDebit,OrderCredit',
            'parent_id' => 'nullable',
            'level' => 'required|integer|min:1|max:6'
        ]);

        $account->update($request->all());
        return response()->json([
            'success' => true,
            'message' => 'Asiento contable actualizado exitosamente',
            'data' => $account
        ], 200);
    }

    /**
     * Delete the specified account.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $account = ChartOfAccount::findOrFail($id);
        $account->delete();

        return response()->json(['message' => 'Account deleted successfully']);
    }

    public function getChildren($parent_id)
    {
        $children = ChartOfAccount::where('parent_id', $parent_id)->get();

        // Si el padre no es 0, también enviamos sus datos
        $parent = null;
        if ($parent_id != 0) {
            $parent = ChartOfAccount::find($parent_id);
        }

        return response()->json([
            'parent' => $parent, // Información del nivel superior
            'children' => $children, // Listado de cuentas hijas
        ]);
    }

    public function tree()
    {
        $accounts = ChartOfAccount::orderBy('code')->get();

        $tree = $this->buildTree($accounts);

        return response()->json($tree);
    }

    private function buildTree($accounts)
    {
        $items = [];
        $tree = [];

        // Mapeo por ID
        foreach ($accounts as $account) {
            $items[$account->id] = [
                'id' => $account->id,
                'code' => $account->code,
                'label' => $account->name,
                'level' => $account->level,
                'children' => []
            ];
        }

        // Construir jerarquía
        foreach ($accounts as $account) {
            if ($account->parent_id && isset($items[$account->parent_id])) {
                $items[$account->parent_id]['children'][] = &$items[$account->id];
            } else {
                $tree[] = &$items[$account->id];
            }
        }

        return $tree;
    }

    public function tables() {
        $account_sale_configurations = ChartAccountSaleConfiguration::get()->map(function ($item) {
            $accountName = ChartOfAccount::where('code', $item->income_account)->value('name');
            $salesReturnsAccountName = ChartOfAccount::where('code', $item->sales_returns_account)->value('name');
            $inventoryAccountName = ChartOfAccount::where('code', $item->inventory_account)->value('name');
            $saleCostAccountName = ChartOfAccount::where('code', $item->sale_cost_account)->value('name');

            return [
                'id' => $item->id,
                'income_account' => $item->income_account . ' - ' . ($accountName ?? ''),
                'sales_returns_account' => $item->sales_returns_account . ' - ' . ($salesReturnsAccountName ?? ''),
                'inventory_account' => $item->inventory_account . ' - ' . ($inventoryAccountName ?? ''),
                'sale_cost_account' => $item->sale_cost_account . ' - ' . ($saleCostAccountName ?? ''),
                'accounting_clasification' => $item->accounting_clasification,
            ];
        });

        return [
            'chart_accounts_sales'=> ChartOfAccount::where('level','>=',4)->get(),
            'chart_accounts_purchases' => ChartOfAccount::where('level','>=',4)->get(),
            'account_sale_configurations' => $account_sale_configurations,
            'chart_account_configurations' => AccountingChartAccountConfiguration::first(),
            'payroll_account_configurations' => PayrollAccountConfiguration::first(),
        ];
    }

    public function accountConfiguration(Request $request)
    {
        $request->validate([
            'inventory_account' => 'nullable',
            'inventory_adjustment_account' => 'nullable',
            'sale_cost_account' => 'nullable',
            'customer_receivable_account' => 'nullable',
            'customer_returns_account' => 'nullable',
            'supplier_payable_account' => 'nullable',
            'supplier_returns_account' => 'nullable',
            'retained_earning_account' => 'nullable',
            'profit_period_account' => 'nullable',
            'lost_period_account' => 'nullable',
            'adjustment_opening_balance_banks_account' => 'nullable',
            'adjustment_opening_balance_banks_inventory' => 'nullable',
            'payroll_account' => 'nullable',
        ]);

        // Buscar el primer registro o crear uno nuevo
        $account = AccountingChartAccountConfiguration::first();
        if (!$account) {
            $account = AccountingChartAccountConfiguration::create($request->all());
        } else {
            $account->update($request->all());
        }

        return response()->json([
            'success' => true,
            'message' => 'Configuración actualizada exitosamente',
            'data' => $account
        ], 200);
    }

    public function payrollAccountConfiguration(Request $request)
    {
        $fields = [
            'salary_account',
            'transportation_allowance_account',
            'health_account',
            'pension_account',
            'vacation_account',
            'service_bonus_account',
            'extra_service_bonus_account',
            'severance_account',
            'severance_interest_account',
            'other_bonuses_account',
            'net_payable_account',
        ];
        $data = $request->only($fields);

        $config = PayrollAccountConfiguration::first();
        if (!$config) {
            $config = PayrollAccountConfiguration::create($data);
        } else {
            $config->update($data);
        }

        return response()->json([
            'success' => true,
            'message' => 'Configuración de cuentas de nómina actualizada exitosamente',
            'data' => $config
        ], 200);
    }

    public function allAccounts()
    {
        // Solo cuentas de movimiento (nivel >= 1), puedes ajustar el nivel si lo necesitas
        $accounts = ChartOfAccount::where('level', '>=', 1)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);
        return response()->json(['data' => $accounts]);
    }

    public function recordsByGroups(Request $request)
    {
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);
        $value = $request->input('value', '');

        $query = ChartOfAccount::with('parent')
            ->where(function($q) {
                $q->where('code', 'like', '14%')
                ->orWhere('code', 'like', '51%');
            });

        if (!empty($value)) {
            $query->where(function($q) use ($value) {
                $q->where('code', 'like', "%$value%")
                ->orWhere('name', 'like', "%$value%");
            });
        }

        $query->orderBy('code', 'asc');

        $entries = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            "data" => $entries->items(),
            "links" => [
                "first" => $entries->url(1),
                "last" => $entries->url($entries->lastPage()),
                "prev" => $entries->previousPageUrl(),
                "next" => $entries->nextPageUrl(),
            ],
            "meta" => [
                "current_page" => $entries->currentPage(),
                "from" => $entries->firstItem(),
                "last_page" => $entries->lastPage(),
                "path" => request()->url(),
                "per_page" => (string) $entries->perPage(),
                "to" => $entries->lastItem(),
                "total" => $entries->total(),
            ]
        ]);
    }

    public function importExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv,txt|max:2048',
        ]);

        try {
            Excel::import(new ChartOfAccountImport, $request->file('file'));

            return response()->json([
                'success' => true,
                'message' => 'Importación completada correctamente.'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al importar PUC: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al procesar el archivo. Verifique el formato.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
