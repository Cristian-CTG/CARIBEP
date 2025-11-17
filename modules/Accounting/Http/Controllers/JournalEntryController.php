<?php

namespace Modules\Accounting\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Barryvdh\DomPDF\Facade as PDF;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalPrefix;
use Modules\Accounting\Models\ThirdParty;
use App\Models\Tenant\Person;
use Modules\Accounting\Models\ChartOfAccount;
use Modules\Payroll\Models\Worker;
use App\Models\Tenant\Seller;
use Modules\Accounting\Http\Resources\JournalEntryDetailResource;
use Modules\Factcolombia1\Models\Tenant\TypeIdentityDocument;
use Modules\Factcolombia1\Models\TenantService\PayrollTypeDocumentIdentification;
use Modules\Factcolombia1\Models\SystemService\TypeDocumentIdentification;
use App\Models\Tenant\BankAccount;
use Modules\Factcolombia1\Models\Tenant\PaymentMethod;

/*
 * Class JournalEntryController
 * Controlador para gestionar los asientos contables
 */
class JournalEntryController extends Controller
{

    public function columns()
    {
        return [
            'date' => 'Fecha generado',
            'daterange' => 'Rango de fechas'
        ];
    }

    public function records(Request $request)
    {
        $perPage = $request->input('per_page', 20); // Número de registros por página
        $page = $request->input('page', 1); // Página actual
        $column = $request->input('column', 'date'); // Columna para buscar (por defecto 'date')
        $value = $request->input('value', ''); // Valor de búsqueda
        $journal_prefix_id = $request->input('journal_prefix_id', null); // Filtro por journal_prefix_id
        $status = $request->input('status', null); // Filtro por estado

        // Construir la consulta base
        $query = JournalEntry::with('journal_prefix');

        // Aplicar filtro si el valor no está vacío
        if (!empty($value)) {
            if($column == 'daterange') {
                $dates = explode('_', $value);
                if(count($dates) == 2) {
                    $query->whereBetween('date', [trim($dates[0]), trim($dates[1])]);
                }
            } else {
                $query->where($column, 'like', "%$value%");
            }
        }

        if(!empty($journal_prefix_id)) {
            $query->where('journal_prefix_id', $journal_prefix_id);
        }

        if(!empty($status)) {
            $query->where('status', $status);
        }

        // Obtener datos paginados
        $entries = $query->with('journal_prefix')->orderBy('created_at', 'desc')->paginate($perPage, ['*'], 'page', $page);

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

    public function store(Request $request)
    {
        $request->validate([
            'journal_prefix_id' => 'required|integer',
            'date' => 'required|date',
            'description' => 'required|string',
        ]);

        $request->merge(['status' => 'draft']);

        $entry = JournalEntry::createWithNumber($request->only(['date', 'journal_prefix_id', 'description', 'status']));

        foreach ($request->details as $detail) {
            $thirdPartyId = null;

            if (!empty($detail['third_party_id'])) {
                if (strpos($detail['third_party_id'], 'person_') === 0) {
                    $personId = (int)str_replace('person_', '', $detail['third_party_id']);
                    $person = Person::find($personId);
                    $documentType = null;
                    if ($person->identity_document_type_id) {
                        $typeDoc = TypeIdentityDocument::find($person->identity_document_type_id);
                        $documentType = $typeDoc ? $typeDoc->code : null;
                    }

                    if ($person) {
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
                } elseif (strpos($detail['third_party_id'], 'worker_') === 0) {
                    $workerId = (int)str_replace('worker_', '', $detail['third_party_id']);
                    $worker = Worker::find($workerId);
                    $documentType = null;
                    if ($worker->payroll_type_document_identification_id) {
                        $typeDoc = PayrollTypeDocumentIdentification::find($worker->payroll_type_document_identification_id);
                        $documentType = $typeDoc ? $typeDoc->code : null;
                    }
                    if ($worker) {
                        $thirdParty = ThirdParty::updateOrCreate(
                            ['document' => $worker->identification_number, 'type' => 'employee'],
                            ['name' => $worker->full_name, 'email' => $worker->email, 'address' => $worker->address, 'phone' => $worker->cellphone, 'document_type' => $documentType, 'origin_id' => $worker->id]
                        );
                        $thirdPartyId = $thirdParty->id;
                    }
                } elseif (strpos($detail['third_party_id'], 'seller_') === 0) {
                    $sellerId = (int)str_replace('seller_', '', $detail['third_party_id']);
                    $seller = Seller::find($sellerId);
                    $documentType = null;
                    if ($seller->type_document_identification_id) {
                        $typeDoc = TypeDocumentIdentification::find($seller->type_document_identification_id);
                        $documentType = $typeDoc ? $typeDoc->code : null;
                    }
                    if ($seller) {
                        $thirdParty = ThirdParty::updateOrCreate(
                            ['document' => $seller->document_number, 'type' => 'seller'],
                            ['name' => $seller->full_name, 'email' => $seller->email, 'address' => $seller->address, 'phone' => $seller->phone, 'document_type' => $documentType, 'origin_id' => $seller->id]
                        );
                        $thirdPartyId = $thirdParty->id;
                    }
                }
            }
            $entry->details()->create([
                'chart_of_account_id' => $detail['chart_of_account_id'],
                'debit' => $detail['debit'],
                'credit' => $detail['credit'],
                'third_party_id' => $thirdPartyId,
                'bank_account_id' => $detail['bank_account_id'] ?? null,
                'payment_method_name' => $detail['payment_method_name'] ?? null,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Asiento contable creado exitosamente',
            'data' => $entry
        ]);
    }

    public function show($id)
    {
        $entry = JournalEntry::with([
            'journal_prefix',
            'details.chartOfAccount',
            'details.thirdParty',
            'details.bankAccount'
        ])->findOrFail($id);

        // Mapear detalles con cuenta y tercero completos
        $details = $entry->details->map(function ($detail) {
            $account = $this->getAccountInfo($detail->chart_of_account_id);
            $third = $this->getThirdPartyInfo(optional($detail->thirdParty)->origin_id, optional($detail->thirdParty)->type);

            return [
                'id' => $detail->id,
                'chart_of_account_id' => $detail->chart_of_account_id,
                'chart_of_account_label' => $account['label'] ?? null,
                'debit' => $detail->debit,
                'credit' => $detail->credit,
                'third_party_id' => $third['id'] ?? null,
                'third_party_label' => $third['name'] ?? null,
                'third_party_type' => optional($detail->thirdParty)->type,
                'bank_account_id' => $detail->bank_account_id,
                'payment_method_name' => $detail->payment_method_name,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $entry->id,
                'date' => $entry->date,
                'journal_prefix_id' => $entry->journal_prefix_id,
                'description' => $entry->description,
                'details' => $details,
                'journal_prefix' => $entry->journal_prefix,
                'status' => $entry->status,
                'number' => $entry->number,
            ]
        ]);
    }

    public function update(Request $request, $id)
    {
        $entry = JournalEntry::findOrFail($id);
        if ($entry->status == 'posted') {
            return response()->json(['message' => 'No se puede modificar un asiento publicado'], 403);
        }
        $oldPrefixId = $entry->journal_prefix_id;
        $newPrefixId = $request->journal_prefix_id;

        // Si el prefijo cambia, primero actualizamos el número SIN tocar el prefijo todavía
        if ($oldPrefixId != $newPrefixId) {

            // Generar nuevo número para el nuevo prefijo
            $newNumber = JournalEntry::getNextNumber($newPrefixId);

            // Actualizar ambos al mismo tiempo (prefijo y número)
            $entry->update([
                'journal_prefix_id' => $newPrefixId,
                'number' => $newNumber,
            ]);

        }

        // Actualiza el resto de campos (fecha, descripción)
        $entry->update([
            'date' => $request->date,
            'description' => $request->description,
        ]);

        // Elimina los detalles existentes
        $entry->details()->delete();

        // Crea los nuevos detalles
        foreach ($request->details as $detail) {
            $thirdPartyId = null;

            if (!empty($detail['third_party_id'])) {
                if (strpos($detail['third_party_id'], 'person_') === 0) {
                    $personId = (int)str_replace('person_', '', $detail['third_party_id']);
                    $person = Person::find($personId);
                    $documentType = null;
                    if ($person->identity_document_type_id) {
                        $typeDoc = TypeIdentityDocument::find($person->identity_document_type_id);
                        $documentType = $typeDoc ? $typeDoc->code : null;
                    }
                    if ($person) {
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
                } elseif (strpos($detail['third_party_id'], 'worker_') === 0) {
                    $workerId = (int)str_replace('worker_', '', $detail['third_party_id']);
                    $worker = Worker::find($workerId);
                    $documentType = null;
                    if ($worker->payroll_type_document_identification_id) {
                        $typeDoc = PayrollTypeDocumentIdentification::find($worker->payroll_type_document_identification_id);
                        $documentType = $typeDoc ? $typeDoc->code : null;
                    }
                    if ($worker) {
                        $thirdParty = ThirdParty::updateOrCreate(
                            ['document' => $worker->identification_number, 'type' => 'employee'],
                            [
                                'name' => $worker->full_name,
                                'email' => $worker->email,
                                'address' => $worker->address,
                                'phone' => $worker->cellphone,
                                'document_type' => $documentType,
                                'origin_id' => $worker->id,
                            ]
                        );
                        $thirdPartyId = $thirdParty->id;
                    }
                } elseif (strpos($detail['third_party_id'], 'seller_') === 0) {
                    $sellerId = (int)str_replace('seller_', '', $detail['third_party_id']);
                    $seller = Seller::find($sellerId);
                    $documentType = null;
                    if ($seller->type_document_identification_id) {
                        $typeDoc = TypeDocumentIdentification::find($seller->type_document_identification_id);
                        $documentType = $typeDoc ? $typeDoc->code : null;
                    }
                    if ($seller) {
                        $thirdParty = ThirdParty::updateOrCreate(
                            ['document' => $seller->document_number, 'type' => 'seller'],
                            [
                                'name' => $seller->full_name,
                                'email' => $seller->email,
                                'address' => $seller->address,
                                'phone' => $seller->telephone,
                                'document_type' => $seller->identity_document_type_id,
                                'origin_id' => $seller->id,
                            ]
                        );
                        $thirdPartyId = $thirdParty->id;
                    }
                }
            }

            $entry->details()->create([
                'chart_of_account_id' => $detail['chart_of_account_id'],
                'debit' => $detail['debit'],
                'credit' => $detail['credit'],
                'third_party_id' => $thirdPartyId,
                'bank_account_id' => $detail['bank_account_id'] ?? null,
                'payment_method_name' => $detail['payment_method_name'] ?? null,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Asiento contable actualizado exitosamente',
            'data' => $entry
        ]);
    }

    public function destroy($id)
    {
        $entry = JournalEntry::findOrFail($id);
        if ($entry->status == 'posted') {
            return response()->json(['message' => 'No se puede eliminar un asiento publicado'], 403);
        }
        // Elimina primero los detalles
        $entry->details()->delete();

        $entry->delete();
        return response()->json(['success' => true, 'message' => 'Eliminado correctamente']);
    }

    public function requestApproval($id)
    {
        $journalEntry = JournalEntry::findOrFail($id);

        if (!in_array($journalEntry->status, ['draft', 'rejected'])) {
            return response()->json(['error' => 'Solo los asientos en borrador o rechazados pueden enviarse a aprobación'], 400);
        }

        if (!$journalEntry->canBeApproved()) {
            return response()->json(['error' => 'El asiento no está balanceado'], 422);
        }

        $journalEntry->update(['status' => 'pending_approval']);

        return response()->json(['message' => 'Asiento enviado para aprobación'], 200);
    }

    public function approve($id)
    {
        $journalEntry = JournalEntry::where('status', 'pending_approval')->findOrFail($id);

        if (!$journalEntry->canBeApproved()) {
            return response()->json(['error' => 'El asiento no está balanceado'], 400);
        }

        $journalEntry->update(['status' => 'posted']);

        return response()->json(['message' => 'Asiento contable aprobado exitosamente'], 200);
    }

    public function reject(Request $request, $id)
    {
        $journalEntry = JournalEntry::where('status', 'pending_approval')->findOrFail($id);

        $journalEntry->update(['status' => 'draft']); // Se mantiene en borrador

        return response()->json(['message' => 'Asiento contable rechazado con observaciones'], 200);
    }

    public function index()
    {
        return view('accounting::journal_entries.index');
    }

    public function getPdf($id)
    {
        $journalEntry = JournalEntry::with('journal_prefix', 'details.bankAccount', 'details.thirdParty', 'details.chartOfAccount')->findOrFail($id);
        $pdf = PDF::loadView('accounting::pdf.journal_entry', compact('journalEntry'));
        return $pdf->stream("asiento_contable.pdf");
    }

    public function bankAccounts()
    {
        // Devuelve solo bancos activos
        $bankAccounts = BankAccount::where('status', 1)
            ->with('bank')
            ->get();

        return response()->json($bankAccounts);
    }

    public function nextNumber(Request $request)
    {
        $request->validate([
            'journal_prefix_id' => 'required|integer'
        ]);

        $prefixId = $request->journal_prefix_id;
        $number = JournalEntry::getNextNumber($prefixId);

        return response()->json([
            'success' => true,
            'number' => $number
        ]);
    }

    public function paymentMethods()
    {
        // Devuelve todos los métodos de pago
        $methods = PaymentMethod::all();

        return response()->json($methods);
    }
    /**
     * Retorna datos resumidos de una cuenta contable.
     */
    private function getAccountInfo($id)
    {
        $account = ChartOfAccount::find($id);
        if (!$account) return null;

        return [
            'id' => $account->id,
            'label' => $account->code . ' - ' . $account->name
        ];
    }

    /**
     * Retorna datos resumidos del tercero según su tipo y origen.
     */
    private function getThirdPartyInfo($originId, $type)
    {
        if (!$originId || !$type) return null;

        switch ($type) {
            case 'customers':
            case 'suppliers':
                $person = Person::find($originId);
                return $person
                    ? ['id' => 'person_' . $person->id, 'name' => $person->name . ' (' . $person->number . ')']
                    : null;

            case 'employee':
                $worker = Worker::find($originId);
                return $worker
                    ? ['id' => 'worker_' . $worker->id, 'name' => $worker->full_name . ' (' . $worker->identification_number . ')']
                    : null;

            case 'seller':
                $seller = Seller::find($originId);
                return $seller
                    ? ['id' => 'seller_' . $seller->id, 'name' => $seller->full_name . ' (' . $seller->document_number . ')']
                    : null;

            default:
                return null;
        }
    }

    /**
     * Buscar cuentas contables por código, nombre o prefijo numérico
     * Permite que si se escribe "9" retorne todas las cuentas que comiencen con 9.
     */
    public function searchAccounts(Request $request)
    {
        $query = trim($request->input('search', ''));
        $limit = $request->input('limit', 50);

        $accounts = ChartOfAccount::query()
            ->when($query, function ($q) use ($query) {
                // Si es un número (como "9"), buscar códigos que empiecen con ese prefijo
                if (is_numeric($query)) {
                    $q->where('code', 'like', "{$query}%");
                } else {
                    // Buscar por coincidencia parcial en código o nombre
                    $q->where(function ($sub) use ($query) {
                        $sub->where('code', 'like', "%{$query}%")
                            ->orWhere('name', 'like', "%{$query}%");
                    });
                }
            })
            ->orderBy('code')
            ->limit($limit)
            ->get(['id', 'code', 'name']);

        return response()->json([
            'data' => $accounts
        ]);
    }
}
