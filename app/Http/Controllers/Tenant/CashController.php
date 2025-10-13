<?php
namespace App\Http\Controllers\Tenant;

use App\Imports\ItemsImport;
use App\Models\Tenant\Catalogs\AffectationIgvType;
use App\Models\Tenant\Catalogs\AttributeType;
use App\Models\Tenant\Catalogs\CurrencyType;
use App\Models\Tenant\Catalogs\SystemIscType;
use App\Models\Tenant\Catalogs\UnitType;
use App\Models\Tenant\User;
use App\Models\Tenant\Company;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\CashRequest;
use App\Http\Resources\Tenant\CashCollection;
use App\Http\Resources\Tenant\CashResource;
use Modules\Item\Models\Category; //se agrega un nuevo Modelo
use App\Models\Tenant\Cash;
use App\Models\Tenant\CashDocument;
use Exception;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel;
use Barryvdh\DomPDF\Facade as PDF;
use App\Models\Tenant\DocumentItem;
use App\Models\Tenant\PaymentMethodType;
use App\Models\Tenant\ConfigurationPos;


class CashController extends Controller
{
    public function index()
    {
        return view('tenant.cash.index');
    }

    public function columns()
    {
        return [
            'income' => 'Ingresos',
            // 'expense' => 'Egresos',
        ];
    }

    public function records(Request $request)
    {
    $records = Cash::where($request->column, 'like', "%{$request->value}%")
            ->whereTypeUser()
            ->orderBy('id', 'desc');


        return new CashCollection($records->paginate(config('tenant.items_per_page')));
    }

    public function create()
    {
        return view('tenant.items.form');
    }

    public function tables()
    {
        $user = auth()->user();
        $type = $user->type;
        $users = array();

        switch($type)
        {
            case 'admin':
                $users = User::where('type', 'seller')->get();
                $users->push($user);
                break;
            case 'seller':
                $users = User::where('id', $user->id)->get();
                break;
        }

        $establishment_id = $user->establishment_id;

        $resolutions = ConfigurationPos::select('id', 'prefix', 'resolution_number')
            ->where(function($query) use ($establishment_id) {
                $query->where('show_in_establishments', 'all')
                    ->orWhere(function($q) use ($establishment_id) {
                        $q->where('show_in_establishments', 'custom')
                            ->whereJsonContains('establishment_ids', $establishment_id);
                    });
            })
            ->get();

        return compact('users', 'user', 'resolutions');
    }

    public function opening_cash()
    {

        $cash = Cash::where([['user_id', auth()->user()->id],['state', true]])->first();

        return compact('cash');
    }

    public function opening_cash_check($user_id)
    {
        $cash = Cash::where([['user_id', $user_id],['state', true]])->first();
        return compact('cash');
    }


    public function record($id)
    {
        $record = new CashResource(Cash::findOrFail($id));

        return $record;
    }

    public function store(CashRequest $request) {

        $id = $request->input('id');
        $cash = Cash::firstOrNew(['id' => $id]);
        $cash->fill($request->all());

        if(!$id){
            $cash->date_opening = date('Y-m-d');
            $cash->time_opening = date('H:i:s');
        }

        $cash->save();

        return [
            'success' => true,
            'message' => ($id)?'Caja actualizada con éxito':'Caja aperturada con éxito'
        ];

    }

    public function close($id) {

        $cash = Cash::findOrFail($id);

        $cash->date_closed = date('Y-m-d');
        $cash->time_closed = date('H:i:s');

        $final_balance = $cash->getSumCashFinalBalance();
        $cash->final_balance = round($final_balance + $cash->beginning_balance, 2);
        $cash->income = round($final_balance, 2);
        $cash->state = false;
        $cash->save();

        return [
            'success' => true,
            'message' => 'Caja cerrada con éxito',
        ];

        // $final_balance = 0;

        // foreach ($cash->cash_documents as $cash_document) {


        //     if($cash_document->sale_note){

        //         // $final_balance += ($cash_document->sale_note->currency_type_id == 'PEN') ? $cash_document->sale_note->total : ($cash_document->sale_note->total * $cash_document->sale_note->exchange_rate_sale);

        //         $final_balance += $cash_document->sale_note->total;

        //     }
        //     else if($cash_document->document){

        //         // $final_balance += ($cash_document->document->currency_type_id == 'PEN') ? $cash_document->document->total : ($cash_document->document->total * $cash_document->document->exchange_rate_sale);

        //         $final_balance += $cash_document->document->total;

        //     }
        //     else if($cash_document->expense_payment){

        //         // $final_balance -= ($cash_document->expense_payment->expense->currency_type_id == 'PEN') ? $cash_document->expense_payment->payment:($cash_document->expense_payment->payment  * $cash_document->expense_payment->expense->exchange_rate_sale);
        //         $final_balance -= $cash_document->expense_payment->payment;

        //     }

        // }

    }


    public function cash_document(Request $request) {

        $cash = Cash::where([['user_id',auth()->user()->id],['state',true]])->first();
        $cash->cash_documents()->create($request->all());

        return [
            'success' => true,
            'message' => 'Venta con éxito',
        ];
    }


    public function destroy($id)
    {
        $cash = Cash::findOrFail($id);

        if($cash->global_destination->count() > 0){
            return [
                'success' => false,
                'message' => 'No puede eliminar la caja, tiene transacciones relacionadas'
            ];
        }

        $cash->delete();

        return [
            'success' => true,
            'message' => 'Caja eliminada con éxito'
        ];
    }

    //Se modifica la funcion report()
    public function report($cashId, $electronic_type = 'all') {
        $cash = Cash::with('cash_documents')->findOrFail($cashId);
        $company = Company::first();

        // Unir fecha y hora para mayor precisión
        $start = $cash->date_opening . ' ' . $cash->time_opening;
        $end = $cash->date_closed
            ? $cash->date_closed . ' ' . $cash->time_closed
            : $cash->date_opening . ' 23:59:59';

        $filtered_documents = $cash->cash_documents()
            ->whereHas('document_pos', function($query) use ($start, $end, $electronic_type) {
                $query->whereRaw("created_at >= ?", [$start])
                    ->whereRaw("created_at <= ?", [$end]);
                if ($electronic_type !== 'all' && $electronic_type !== 'resumido') {
                    $query->where('electronic', $electronic_type);
                }
            })->get();
        // Calcular $cashEgress solo para documentos filtrados
        $cashEgress = $filtered_documents->sum(function ($cashDocument) {
            return $cashDocument->expense_payment ? $cashDocument->expense_payment->payment : 0;
        });

        // Filtrar expense_payments según el tipo
        $expensePayments = $filtered_documents->filter(function ($doc) {
            return !is_null($doc->expense_payment_id);
        })->map->expense_payment;

        $methods_payment = collect();
        
        foreach ($filtered_documents as $cash_document) {
            if ($cash_document->document_pos && $cash_document->document_pos->payments) {
                foreach ($cash_document->document_pos->payments as $payment) {
                    $method_key = null;
                    $method_name = '';
                    
                    if ($payment->payment_method_type_id) {
                        $method_key = 'type_' . $payment->payment_method_type_id;
                        $method_name = $payment->payment_method_type 
                            ? $payment->payment_method_type->description 
                            : 'Método no especificado';
                    } elseif ($payment->payment_method_id) {
                        $method_key = 'method_' . $payment->payment_method_id;
                        $method_name = $payment->payment_method 
                            ? $payment->payment_method->name 
                            : 'Método no especificado';
                    }
                    
                    if ($method_key) {
                        if (!$methods_payment->has($method_key)) {
                            $methods_payment->put($method_key, (object)[
                                'id' => $method_key,
                                'name' => $method_name,
                                'sum' => 0,
                                'transaction_count' => 0
                            ]);
                        }
                        
                        $method = $methods_payment->get($method_key);
                        $method->sum += $payment->payment;
                        $method->transaction_count++;
                    }
                }
            }
        }

        $methods_payment = $methods_payment->filter(function($method) {
            return $method->sum > 0;
        })->values();

        // Se recuperan las categorías
        $categories = Category::all()->pluck('name', 'id');

        // Filtrar las máquinas según el tipo seleccionado
        $query = ConfigurationPos::select('cash_type', 'plate_number', 'electronic');
        if ($electronic_type !== 'all' && $electronic_type !== 'resumido') {
            $query->where('electronic', $electronic_type);
        }
        $resolutions_maquinas = $query->get();

        // Determinar si es reporte resumido
        $is_resumido = $electronic_type === 'resumido';

        set_time_limit(0);

        $pdf = PDF::loadView('tenant.cash.report_pdf', compact(
            "cash", 
            "company", 
            "methods_payment", 
            "cashEgress", 
            "categories", 
            "resolutions_maquinas", 
            "expensePayments",
            "electronic_type",
            "is_resumido",
            "filtered_documents"
        ));

        $filename = "Reporte_POS - {$cash->user->name} - {$cash->date_opening} {$cash->time_opening}";
        return $pdf->stream($filename . '.pdf');
    }

    public function report_ticket($cashId, $type = 'complete', $electronic_type = 'all') {
        
        $electronic_type = request()->get('electronic_type', $electronic_type);

        $cash = Cash::findOrFail($cashId);
        $company = Company::first();
        $only_head = $type === 'simple' ? 'resumido' : null;

        $start = $cash->date_opening . ' ' . $cash->time_opening;
        $end = $cash->date_closed
            ? $cash->date_closed . ' ' . $cash->time_closed
            : $cash->date_opening . ' 23:59:59';

        $filtered_documents = $cash->cash_documents()
            ->whereHas('document_pos', function($query) use ($start, $end, $electronic_type) {
                $query->whereRaw("created_at >= ?", [$start])
                    ->whereRaw("created_at <= ?", [$end]);
                if ($electronic_type !== 'all' && $electronic_type !== 'resumido') {
                    $query->where('electronic', $electronic_type);
                }
            })->get();

        $cashEgress = $filtered_documents->sum(function ($cashDocument) {
            return $cashDocument->expense_payment ? $cashDocument->expense_payment->payment : 0;
        });

        $methods_payment = collect();
        
        foreach ($filtered_documents as $cash_document) {
            if ($cash_document->document_pos && $cash_document->document_pos->payments) {
                foreach ($cash_document->document_pos->payments as $payment) {
                    $method_key = null;
                    $method_name = '';
                    
                    if ($payment->payment_method_type_id) {
                        $method_key = 'type_' . $payment->payment_method_type_id;
                        $method_name = $payment->payment_method_type 
                            ? $payment->payment_method_type->description 
                            : 'Método no especificado';
                    } elseif ($payment->payment_method_id) {
                        $method_key = 'method_' . $payment->payment_method_id;
                        $method_name = $payment->payment_method 
                            ? $payment->payment_method->name 
                            : 'Método no especificado';
                    }
                    
                    if ($method_key) {
                        if (!$methods_payment->has($method_key)) {
                            $methods_payment->put($method_key, (object)[
                                'id' => $method_key,
                                'name' => $method_name,
                                'sum' => 0,
                                'transaction_count' => 0
                            ]);
                        }
                        
                        $method = $methods_payment->get($method_key);
                        $method->sum += $payment->payment;
                        $method->transaction_count++;
                    }
                }
            }
        }

        // Solo mantener métodos que tengan transacciones (sum > 0)
        $methods_payment = $methods_payment->filter(function($method) {
            return $method->sum > 0;
        })->values();

        $categories = Category::all()->pluck('name', 'id');
        $query = ConfigurationPos::select('cash_type', 'plate_number', 'electronic');
        if ($electronic_type !== 'all' && $electronic_type !== 'resumido') {
            $query->where('electronic', $electronic_type);
        }
        $resolutions_maquinas = $query->get();

        $is_resumido = $electronic_type === 'resumido';

        $view = request()->get('format') === 'ticket'
            ? 'tenant.cash.report_ticket_pdf'
            : 'tenant.cash.report_ticket';

        $pdf = PDF::loadView($view, compact(
            "cash",
            "company",
            "methods_payment",
            "cashEgress",
            "categories",
            "resolutions_maquinas",
            "only_head",
            "is_resumido",
            "filtered_documents",
            "electronic_type"
        ))->setPaper(array(0,0,227,1000));
        $filename = "Reporte_POS - {$cash->user->name} - {$cash->date_opening} {$cash->time_opening}";

        return $pdf->stream($filename . '.pdf');
    }

    public function report_general()
    {

        $cashes = Cash::select('id')->whereDate('date_opening', date('Y-m-d'))->pluck('id');
        $cash_documents =  CashDocument::with('document_pos')->whereNotNull('document_pos_id')->whereIn('cash_id', $cashes)->get();

        $company = Company::first();
        set_time_limit(0);

        $pdf = PDF::loadView('tenant.cash.report_general_pdf', compact("cash_documents", "company"));
        $filename = "Reporte_POS";
        return $pdf->download($filename.'.pdf');

    }

    public function report_products($id)
    {
        $cash = Cash::findOrFail($id);
        $company = Company::first();
        $cash_documents =  CashDocument::select('document_id')->where('cash_id', $cash->id)->get();

        $source = DocumentItem::with('document')->whereIn('document_id', $cash_documents)->get();

        $documents = collect($source)->transform(function($row){
            return [
                'id' => $row->id,
                'number_full' => $row->document->number_full,
                'description' => $row->item->description,
                'quantity' => $row->quantity,
            ];
        });


        $pdf = PDF::loadView('tenant.cash.report_product_pdf', compact("cash", "company", "documents"));

        $filename = "Reporte_POS_PRODUCTOS - {$cash->user->name} - {$cash->date_opening} {$cash->time_opening}";

        return $pdf->stream($filename.'.pdf');
    }
}
