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

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'type' => 'required|string',
            'document' => 'nullable|string',
        ]);
        $third = ThirdParty::create($data);
        return response()->json($third);
    }

    public function syncFromOrigin(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|string', // customers, suppliers, employee, seller
            'origin_id' => 'required|integer',
        ]);

        $type = $data['type'];
        $origin_id = $data['origin_id'];

        // Busca el modelo de origen según el tipo
        switch ($type) {
            case 'customers':
            case 'suppliers':
                $origin = Person::find($origin_id);
                if (!$origin) return response()->json(['error' => 'No encontrado'], 404);
                $name = $origin->name;
                $document = $origin->number;
                $document_type = $origin->identity_document_type_id;
                $address = $origin->address;
                $phone = $origin->telephone;
                $email = $origin->email;
                break;
            case 'employee':
                $origin = Worker::find($origin_id);
                if (!$origin) return response()->json(['error' => 'No encontrado'], 404);
                $name = $origin->full_name;
                $document = $origin->identification_number;
                $document_type = $origin->payroll_type_document_identification_id;
                $address = $origin->address;
                $phone = $origin->cellphone;
                $email = $origin->email;
                break;
            case 'seller':
                $origin = Seller::find($origin_id);
                if (!$origin) return response()->json(['error' => 'No encontrado'], 404);
                $name = $origin->full_name;
                $document = $origin->document_number;
                $document_type = $origin->type_document_identification_id;
                $address = $origin->address;
                $phone = $origin->phone;
                $email = $origin->email;
                break;
            default:
                return response()->json(['error' => 'Tipo no soportado'], 400);
        }

        // Busca o crea el tercero
        $third = ThirdParty::updateOrCreate(
            ['type' => $type, 'origin_id' => $origin_id],
            [
                'name' => $name,
                'document' => $document,
                'document_type' => $document_type,
                'address' => $address,
                'phone' => $phone,
                'email' => $email,
            ]
        );

        return response()->json($third);
    }
    public function allThirdParties(Request $request)
    {
        $search = $request->input('search');
        $results = collect();

        // Clientes y proveedores
        $persons = Person::whereIn('type', ['customers', 'suppliers'])
            ->when($search, function($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                ->orWhere('number', 'like', "%$search%");
            })
            ->limit(10)
            ->get()
            ->map(function($p) {
                return [
                    'id' => 'person_'.$p->id,
                    'name' => $p->number . ' - ' . $p->name . ' ('. ($p->type == 'customers' ? 'Cliente' : 'Proveedor') .')',
                ];
            });
        $results = $results->merge($persons);

        // Empleados
        $workers = Worker::when($search, function($q) use ($search) {
                $q->where('first_name', 'like', "%$search%")
                ->orWhere('surname', 'like', "%$search%")
                ->orWhere('second_surname', 'like', "%$search%")
                ->orWhere('identification_number', 'like', "%$search%");
            })
            ->limit(10)
            ->get()
            ->map(function($w) {
                return [
                    'id' => 'worker_'.$w->id,
                    'name' => $w->identification_number . ' - ' . $w->first_name . ' ' . $w->surname . ' ' . $w->second_surname . ' (Empleado)',
                ];
            });
        $results = $results->merge($workers);

        // Vendedores
        $sellers = Seller::when($search, function($q) use ($search) {
                $q->where('full_name', 'like', "%$search%")
                ->orWhere('document_number', 'like', "%$search%");
            })
            ->limit(10)
            ->get()
            ->map(function($s) {
                return [
                    'id' => 'seller_'.$s->id,
                    'name' => $s->document_number . ' - ' . $s->full_name . ' (Vendedor)',
                ];
            });
        $results = $results->merge($sellers);

        // Limita el total a 10 resultados (puedes ajustar si quieres más)
        $finalResults = $results->take(10)->values();

        return response()->json(['data' => $finalResults]);
    }
}