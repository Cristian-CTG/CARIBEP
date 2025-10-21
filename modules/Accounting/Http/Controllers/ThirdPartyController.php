<?php
namespace Modules\Accounting\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\Tenant\Person;
use Modules\Payroll\Models\Worker;
use Modules\Accounting\Models\ThirdParty;
use Modules\Accounting\Models\JournalEntry;
use App\Models\Tenant\Seller;

class ThirdPartyController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $type = $request->input('type');

        $results = collect();

        if ($type === 'customers' || $type === 'suppliers') {
            $persons = Person::where('type', $type)
                ->when($search, function($q) use ($search) {
                    $q->where('name', 'like', "%$search%")
                    ->orWhere('number', 'like', "%$search%");
                })
                ->limit(20)
                ->get()
                ->map(function($p) {
                    return [
                        'id' => 'person_'.$p->id,
                        'name' => $p->name . ' (' . $p->number . ')',
                    ];
                });
            $results = $results->merge($persons);
        }

        if ($type === 'employee') {
            $workers = Worker::when($search, function($q) use ($search) {
                    $q->where('first_name', 'like', "%$search%")
                    ->orWhere('surname', 'like', "%$search%")
                    ->orWhere('identification_number', 'like', "%$search%");
                })
                ->limit(20)
                ->get()
                ->map(function($w) {
                    return [
                        'id' => 'worker_'.$w->id,
                        'name' => $w->full_name . ' (' . $w->identification_number . ')',
                    ];
                });
            $results = $results->merge($workers);
        }

        if ($type === 'seller') {
            $sellers = Seller::when($search, function($q) use ($search) {
                    $q->where('full_name', 'like', "%$search%")
                    ->orWhere('document_number', 'like', "%$search%");
                })
                ->limit(20)
                ->get()
                ->map(function($s) {
                    return [
                        'id' => 'seller_'.$s->id,
                        'name' => $s->full_name . ' (' . $s->document_number . ')',
                    ];
                });
            $results = $results->merge($sellers);
        }

        return response()->json(['data' => $results->values()]);
    }
}