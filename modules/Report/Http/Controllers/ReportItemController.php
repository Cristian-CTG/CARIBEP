<?php

namespace Modules\Report\Http\Controllers;

use App\Models\Tenant\Catalogs\DocumentType;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade as PDF;
use Modules\Report\Exports\ItemExport;
use Illuminate\Http\Request;
use App\Models\Tenant\Establishment;
use App\Models\Tenant\Document;
use App\Models\Tenant\DocumentItem;
use App\Models\Tenant\Company;
use App\Models\Tenant\DocumentPosItem;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Report\Http\Resources\ItemCollection;
use Modules\Report\Traits\ReportTrait;
use Modules\Sale\Models\RemissionItem;

class ReportItemController extends Controller
{
    use ReportTrait;

public function filter() {
    $document_types = [
        ['id' => 'all', 'description' => 'Todos'],
        ['id' => 'normal', 'description' => 'Electrónica'],
        ['id' => 'pos', 'description' => 'POS'],
        ['id' => 'remission', 'description' => 'Remisiones'],
    ];
    $items = $this->getItems('items');
    $establishments = [];
    return compact('document_types','establishments','items');
}


    public function index() {

        return view('report::items.index');
    }

    public function records(Request $request)
    {
        $records = $this->getRecordsItems($request->all(), DocumentItem::class);

        // Si es una colección, paginar manualmente
        if ($request->document_type === 'all') {
            $page = request()->get('page', 1);
            $perPage = config('tenant.items_per_page');
            $paginated = new LengthAwarePaginator(
                $records->forPage($page, $perPage),
                $records->count(),
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->query()]
            );
            return new ItemCollection($paginated);
        }

        // Si es un query builder, usar paginate normalmente
        return new ItemCollection($records->paginate(config('tenant.items_per_page')));
    }



    public function getRecordsItems($request, $model)
    {
        $document_type = $request['document_type'];
        $item_id = $request['item_id'];
        $period = $request['period'];
        $date_start = $request['date_start'];
        $date_end = $request['date_end'];
        $month_start = $request['month_start'];
        $month_end = $request['month_end'];

        $d_start = null;
        $d_end = null;

        switch ($period) {
            case 'month':
                $d_start = Carbon::parse($month_start.'-01')->format('Y-m-d');
                $d_end = Carbon::parse($month_start.'-01')->endOfMonth()->format('Y-m-d');
                // $d_end = Carbon::parse($month_end.'-01')->endOfMonth()->format('Y-m-d');
                break;
            case 'between_months':
                $d_start = Carbon::parse($month_start.'-01')->format('Y-m-d');
                $d_end = Carbon::parse($month_end.'-01')->endOfMonth()->format('Y-m-d');
                break;
            case 'date':
                $d_start = $date_start;
                $d_end = $date_start;
                // $d_end = $date_end;
                break;
            case 'between_dates':
                $d_start = $date_start;
                $d_end = $date_end;
                break;
        }

        $records = null;

        if ($document_type === 'pos') {
            $records = DocumentPosItem::where('item_id', $item_id)
                ->with('document_pos')
                ->whereHas('document_pos', function($query) use($d_start, $d_end){
                    $query->whereBetween('date_of_issue', [$d_start, $d_end])
                          ->whereIn('state_type_id', ['01','03','05','07','13'])//falta agregar el 11 para las anulaciones
                          ->latest();
                });
        } elseif ($document_type === 'remission') {
            $records = RemissionItem::where('item_id', $item_id)
                ->with('remission')
                ->whereHas('remission', function($query) use($d_start, $d_end){
                    $query->whereBetween('date_of_issue', [$d_start, $d_end])
                          ->whereIn('state_type_id', ['01'])//falta agregar el 11 para las anulaciones
                          ->latest();
                });
        } elseif ($document_type === 'all') {
            // Unir todos los tipos
            $records_normal = DocumentItem::where('item_id', $item_id)
                ->with('document')
                ->whereHas('document', function($query) use($d_start, $d_end){
                    $query->whereBetween('date_of_issue', [$d_start, $d_end])
                          ->filterInvoiceDocument()
                          ->whereIn('state_document_id', [1,2,3,4,5])
                          ->latest();
                });

            $records_pos = DocumentPosItem::where('item_id', $item_id)
                ->with('document_pos')
                ->whereHas('document_pos', function($query) use($d_start, $d_end){
                    $query->whereBetween('date_of_issue', [$d_start, $d_end])
                          ->whereIn('state_type_id', ['01','03','05','07','13'])//falta agregar el 11 para las anulaciones
                          ->latest();
                });

            $records_remission = RemissionItem::where('item_id', $item_id)
                ->with('remission')
                ->whereHas('remission', function($query) use($d_start, $d_end){
                    $query->whereBetween('date_of_issue', [$d_start, $d_end])
                          ->whereIn('state_type_id', ['01'])//falta agregar el 11 para las anulaciones
                          ->latest();
                });

            // Unir colecciones
            $records = $records_normal->get()
                ->merge($records_pos->get())
                ->merge($records_remission->get());

            // Devuelve una colección simple (no paginada)
            return $records;
        } else {
            $records = DocumentItem::where('item_id', $item_id)
                ->with('document')
                ->whereHas('document', function($query) use($d_start, $d_end){
                    $query->whereBetween('date_of_issue', [$d_start, $d_end])
                          ->filterInvoiceDocument()
                          ->whereIn('state_document_id', [1,2,3,4,5])
                          ->latest();
                });
        }

        return $records;

    }


    public function excel(Request $request) {

        $company = Company::first();
        $establishment = ($request->establishment_id) ? Establishment::findOrFail($request->establishment_id) : auth()->user()->establishment;

        $records = $this->getRecordsItems($request->all(), DocumentItem::class);

        // Si es una colección, úsala directamente
        if ($request->document_type === 'all') {
            // $records ya es una colección
        } else {
            // Si es un query builder, obtén la colección
            $records = $records->get();
        }

        return (new ItemExport)
                ->records($records)
                ->company($company)
                ->establishment($establishment)
                ->download('Reporte_Ventas_por_Producto_'.Carbon::now().'.xlsx');

    }
}
