<?php

namespace Modules\Expense\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Expense\Models\Expense;
use Modules\Expense\Models\ExpenseReason;
use Modules\Expense\Models\ExpensePayment;
use Modules\Expense\Models\ExpenseType;
use Modules\Expense\Models\ExpenseMethodType;
use Modules\Expense\Models\ExpenseItem;
use Modules\Expense\Http\Resources\ExpenseCollection;
use Modules\Expense\Http\Resources\ExpenseResource;
use Modules\Expense\Http\Requests\ExpenseRequest;
use Illuminate\Support\Str;
use App\Models\Tenant\Person;
use App\Models\Tenant\Catalogs\CurrencyType;
use App\CoreFacturalo\Requests\Inputs\Common\PersonInput;
use App\Models\Tenant\Establishment;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant\Company;
use Modules\Finance\Traits\FinanceTrait; 
use Modules\Factcolombia1\Models\Tenant\{
    Currency,
};
use Modules\Accounting\Helpers\AccountingEntryHelper;
use Modules\Accounting\Models\AccountingChartAccountConfiguration;
use Modules\Accounting\Models\ChartOfAccount;
use Modules\Accounting\Models\JournalPrefix;
use Modules\Accounting\Models\ThirdParty;
use Modules\Factcolombia1\Models\Tenant\TypeIdentityDocument;


class ExpenseController extends Controller
{

    use FinanceTrait;

    public function index()
    {
        return view('expense::expenses.index');
    }


    public function create()
    {
        return view('expense::expenses.form');
    }

    public function columns()
    {
        return [
            'number' => 'Número',
            'date_of_issue' => 'Fecha de emisión',
        ];
    }


    public function records(Request $request)
    {
        $records = Expense::where($request->column, 'like', "%{$request->value}%")
                            ->whereTypeUser()
                            ->latest();

        return new ExpenseCollection($records->paginate(config('tenant.items_per_page')));
    }

    public function tables()
    {
        $suppliers = $this->table('suppliers');
        $establishment = Establishment::where('id', auth()->user()->establishment_id)->first();
        $expense_types = ExpenseType::get();
        $expense_method_types = ExpenseMethodType::all();
        $expense_reasons = ExpenseReason::all();
        $payment_destinations = $this->getBankAccounts();
        $currencies = Currency::all();

        return compact('suppliers', 'establishment','currencies', 'expense_types', 'expense_method_types', 'expense_reasons', 'payment_destinations');
    }



    public function record($id)
    {
        $record = new ExpenseResource(Expense::findOrFail($id));

        return $record;
    }

    public function store(ExpenseRequest $request)
    {
        $data = self::merge_inputs($request);

        $expense = DB::connection('tenant')->transaction(function () use ($data) {

            $doc = Expense::create($data);
            foreach ($data['items'] as $row)
            {
                $doc->items()->create($row);
            }

            foreach ($data['payments'] as $row)
            {
                $record_payment = $doc->payments()->create($row);
                
                if($row['expense_method_type_id'] == 1){
                    $row['payment_destination_id'] = 'cash';
                }

                $this->createGlobalPayment($record_payment, $row);
            }
            $this->registerAccountingExpenseEntries($doc);

            return $doc;
        });

        return [
            'success' => true,
            'data' => [
                'id' => $expense->id,
            ],
        ];
    }

    private function registerAccountingExpenseEntries($expense)
    {
        $config = AccountingChartAccountConfiguration::first();
        $accountPayable = ChartOfAccount::where('code', $config->supplier_payable_account)->first(); // CxP Proveedores
        $prefix = JournalPrefix::where('prefix', 'GD')->first();

        // Crear o buscar el tercero (proveedor)
        $person = Person::find($expense->supplier_id);
        $thirdPartyId = null;
        if ($person) {
            $documentType = null;
            if (isset($person->identity_document_type_id)) {
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
                    'origin_id' => $person->id,
                ]
            );
            $thirdPartyId = $thirdParty->id;
        }

        // Agrupar ítems por cuenta contable y totalizar
        $grouped = [];
        foreach ($expense->items as $item) {
            // Si no tiene cuenta contable, usa la predeterminada 5195 (gastos varios)
            $account_id = $item->chart_of_account_id ?: ChartOfAccount::where('code', '5195')->value('id');
            if (!isset($grouped[$account_id])) {
                $grouped[$account_id] = 0;
            }
            $grouped[$account_id] += $item->total;
        }

        // Construir los movimientos (débito por cada cuenta, crédito único)
        $movements = [];

        // Débitos (gastos)
        foreach ($grouped as $account_id => $total) {
            $movements[] = [
                'account_id' => $account_id,
                'debit' => $total,
                'credit' => 0,
                'affects_balance' => true,
                'third_party_id' => $thirdPartyId,
            ];
        }

        // Crédito total (Cuentas por pagar)
        $movements[] = [
            'account_id' => $accountPayable->id,
            'debit' => 0,
            'credit' => $expense->total,
            'affects_balance' => true,
            'third_party_id' => $thirdPartyId,
        ];

        // Registrar asiento contable
        $description = 'Registro de gasto #' . $expense->number;

        AccountingEntryHelper::registerEntry([
            'prefix_id' => $prefix ? $prefix->id : null,
            'description' => $description,
            'expense_id' => $expense->id,
            'movements' => $movements,
            'taxes' => [],
            'tax_config' => [],
        ]);
    }

    public static function merge_inputs($inputs)
    {

        $company = Company::active();

        $values = [
            'user_id' => auth()->id(),
            'state_type_id' => '05',
            'soap_type_id' => $company->soap_type_id,
            'external_id' => Str::uuid()->toString(),
            'supplier' => PersonInput::set($inputs['supplier_id']),
        ];

        $inputs->merge($values);

        return $inputs->all();
    }

    public function table($table)
    {
        switch ($table) {
            case 'suppliers':

                $suppliers = Person::whereType('suppliers')->orderBy('name')->get()->transform(function($row) {
                    return [
                        'id' => $row->id,
                        'description' => $row->number.' - '.$row->name,
                        'name' => $row->name,
                        'number' => $row->number,
                        'identity_document_type_id' => $row->identity_document_type_id,
                        'identity_document_type_code' => $row->identity_document_type->code
                    ];
                });
                return $suppliers;

                break;
            default:

                return [];

                break;
        }
    }

    public function voided ($record)
    {
        try {
            $expense = Expense::findOrFail($record);
            $expense->state_type_id = 11;
            $expense->save();
            return [
                'success' => true,
                'data' => [
                    'id' => $expense->id,
                ],
                'message' => 'Gasto anulado exitosamente',
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'data' => [
                    'id' => $record,
                ],
                'message' => 'Falló al anular',
            ];
        }
    }

    public function searchChartOfAccounts(Request $request)
    {
        $query = trim($request->input('search', ''));
        $limit = $request->input('limit', 50);

        $accounts = ChartOfAccount::query()
            ->when($query, function ($q) use ($query) {
                // Si el texto ingresado es numérico, busca por prefijo del código
                if (is_numeric($query)) {
                    $q->where('code', 'like', "{$query}%");
                } else {
                    // Si es texto, busca en nombre o código
                    $q->where(function ($sub) use ($query) {
                        $sub->where('name', 'like', "%{$query}%")
                            ->orWhere('code', 'like', "%{$query}%");
                    });
                }
            })
            ->orderBy('code')
            ->limit($limit)
            ->get(['id', 'code', 'name']);

        return response()->json(['data' => $accounts]);
    }

}
