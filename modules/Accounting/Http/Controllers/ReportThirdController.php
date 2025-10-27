<?php
namespace Modules\Accounting\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounting\Models\ThirdParty;
use Barryvdh\DomPDF\Facade as PDF;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Accounting\Models\JournalEntryDetail;

class ReportThirdController extends Controller
{
    public function index()
    {
        return view('accounting::reports.third_report');
    }

    public function records(Request $request)
    {
        $search = $request->input('search');
        $type = $request->input('type');

        $query = ThirdParty::query();

        if ($type) {
            $query->where('type', $type);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                ->orWhere('document', 'like', "%$search%");
            });
        }

        $third_parties = $query->orderBy('name')->get();

        // Devuelve solo los datos necesarios para el select
        $thirds = $third_parties->map(function($t) {
            return [
                'id' => $t->id,
                'name' => $t->name . ' (' . $t->document . ')',
            ];
        });

        return response()->json([
            'data' => $thirds
        ]);
    }

    public function export(Request $request)
    {
        $format = $request->input('export', 'pdf');
        $type = $request->input('type');
        $third_id = $request->input('third_id');

        $third = ThirdParty::find($third_id);
        $third_name = $third ? $third->name : '';
        $third_document = $third ? $third->document : '';

        // Buscar detalles de asientos del mes y del tercero
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');

        $details = JournalEntryDetail::where('third_party_id', $third ? $third->id : null)
            ->whereHas('journalEntry', function($q) use ($start_date, $end_date) {
                $q->whereBetween('date', [$start_date, $end_date])
                ->where('status', 'posted');
            })
            ->with(['journalEntry', 'chartOfAccount'])
            ->orderBy('id')
            ->get();

        // Agrupar por código y nombre de cuenta, separando débitos y créditos
        $grouped = [];
        foreach ($details as $d) {
            $key = $d->chartOfAccount->code . '|' . ($d->chartOfAccount->name ?? '');
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'codigo' => $d->chartOfAccount->code,
                    'cuenta' => $d->chartOfAccount->name ?? '',
                    'debito' => 0,
                    'credito' => 0,
                ];
            }
            $grouped[$key]['debito'] += $d->debit;
            $grouped[$key]['credito'] += $d->credit;
        }

        // Genera filas separadas si hay ambos valores
        $rows = [];
        foreach ($grouped as $g) {
            if ($g['debito'] > 0) {
                $rows[] = [
                    'codigo' => $g['codigo'],
                    'cuenta' => $g['cuenta'],
                    'debito' => $g['debito'],
                    'credito' => 0,
                ];
            }
            if ($g['credito'] > 0) {
                $rows[] = [
                    'codigo' => $g['codigo'],
                    'cuenta' => $g['cuenta'],
                    'debito' => 0,
                    'credito' => $g['credito'],
                ];
            }
        }

        $data = [
            'third_name' => $third_name,
            'third_document' => $third_document,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'rows' => $rows,
        ];

        $pdf = PDF::loadView('accounting::reports.third_report_pdf', $data)->setPaper('a4', 'landscape');
        return $pdf->stream('ReporteTercero.pdf');
    }
    public function exportAllThirds(Request $request)
    {
        $type = $request->input('type');
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');

        // Filtra los terceros por tipo
        $thirds = ThirdParty::query();
        if ($type) {
            $thirds->where('type', $type);
        }
        $thirds = $thirds->get();

        $rows = [];

        foreach ($thirds as $third) {
            // Busca movimientos en el rango de fechas
            $details = JournalEntryDetail::where('third_party_id', $third->id)
                ->whereHas('journalEntry', function($q) use ($start_date, $end_date) {
                    $q->whereBetween('date', [$start_date, $end_date])
                    ->where('status', 'posted');
                })
                ->with('chartOfAccount')
                ->get();

            // Agrupa por cuenta
            $grouped = [];
            foreach ($details as $d) {
                $key = $d->chartOfAccount->code . '|' . ($d->chartOfAccount->name ?? '');
                if (!isset($grouped[$key])) {
                    $grouped[$key] = [
                        'codigo' => $d->chartOfAccount->code,
                        'cuenta' => $d->chartOfAccount->name ?? '',
                        'debito' => 0,
                        'credito' => 0,
                    ];
                }
                $grouped[$key]['debito'] += $d->debit;
                $grouped[$key]['credito'] += $d->credit;
            }

            // Genera filas para este tercero
            foreach ($grouped as $g) {
                if ($g['debito'] > 0 || $g['credito'] > 0) {
                    $rows[] = [
                        'tipo' => $third->getTypeName(),
                        'nombre' => $third->name,
                        'documento' => $third->document,
                        'codigo' => $g['codigo'],
                        'cuenta' => $g['cuenta'],
                        'debito' => $g['debito'],
                        'credito' => $g['credito'],
                    ];
                }
            }
        }

        $tipo_nombre = '';
        if ($type) {
            $tipo_nombre = (new ThirdParty(['type' => $type]))->getTypeName();
        }

        $data = [
            'rows' => $rows,
            'tipo' => $tipo_nombre,
            'start_date' => $start_date,
            'end_date' => $end_date,
        ];

        $pdf = PDF::loadView('accounting::reports.third_report_all_pdf', $data)->setPaper('a4', 'landscape');
        return $pdf->stream('ReporteTodosTerceros.pdf');
    }
}