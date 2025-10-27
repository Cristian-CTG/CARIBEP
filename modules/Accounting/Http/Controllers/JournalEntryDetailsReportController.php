<?php

namespace Modules\Accounting\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounting\Models\JournalEntryDetail;
use Carbon\Carbon;
use Mpdf\Mpdf;

class JournalEntryDetailsReportController extends Controller
{
    public function index()
    {
        return view('accounting::reports.journal_entry_details_report');
    }

    public function columns()
    {
        return [
            'date' => 'Fecha',
            'number' => 'Número',
            'journal_prefix_id' => 'Tipo Comprobante',
            'account' => 'Cuenta',
            'description' => 'Descripción',
            'debit' => 'Debe',
            'credit' => 'Haber',
        ];
    }

    public function records(Request $request)
    {
        $query = JournalEntryDetail::with([
            'journalEntry.journal_prefix', // <-- nombre correcto
            'journalEntry',
            'chartOfAccount',
            'thirdParty'
        ]);

        if ($request->filled('month')) {
            $query->whereHas('journalEntry', function($q) use ($request) {
                $q->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$request->month]);
            });
        }
        if ($request->filled('journal_prefix_id')) {
            $query->whereHas('journalEntry', function($q) use ($request) {
                $q->where('journal_prefix_id', $request->journal_prefix_id);
            });
        }
        if ($request->filled('number_from')) {
            $query->whereHas('journalEntry', function($q) use ($request) {
                $q->where('number', '>=', $request->number_from);
            });
        }
        if ($request->filled('number_to')) {
            $query->whereHas('journalEntry', function($q) use ($request) {
                $q->where('number', '<=', $request->number_to);
            });
        }
        // Calcular totales según los filtros (sin paginación)
        $totalsQuery = clone $query;
        $total_debit = $totalsQuery->sum('debit');
        $total_credit = $totalsQuery->sum('credit');

        $records = $query->orderBy('id', 'desc')->paginate(20);

        // Agregar el nombre del tercero implicado a cada registro
        foreach ($records as $detail) {
            $entry = $detail->journalEntry;
            $thirdParty = $detail->thirdParty;
            if ($thirdParty && isset($thirdParty->name)) {
                $thirdPartyName = $thirdParty->name;
            } else {
                $tp = $this->getThirdPartyFromEntry($entry);
                $thirdPartyName = isset($tp['name']) ? $tp['name'] : '-';
            }
            $detail->third_party_name = $thirdPartyName;
        }

        return [
            'data' => $records->items(),
            'meta' => [
                'total' => $records->total(),
                'current_page' => $records->currentPage(),
                'per_page' => $records->perPage(),
                'total_debit' => $total_debit,
                'total_credit' => $total_credit,
            ]
        ];
    }
    public function export(Request $request)
    {
        $format = $request->input('format', 'pdf');
        if ($format === 'pdf') {
            return $this->exportPdf($request);
        }
        return response()->json(['error' => 'Formato no soportado'], 400);
    }

    private function exportPdf(Request $request)
    {
        $query = JournalEntryDetail::with([
            'journalEntry.journal_prefix',
            'journalEntry',
            'chartOfAccount',
            'thirdParty'
        ]);

        // Filtro por mes
        if ($request->filled('month')) {
            $query->whereHas('journalEntry', function($q) use ($request) {
                $q->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$request->month]);
            });
        }
        // Filtro por tipo de comprobante
        if ($request->filled('journal_prefix_id')) {
            $query->whereHas('journalEntry', function($q) use ($request) {
                $q->where('journal_prefix_id', $request->journal_prefix_id);
            });
        }
        // Filtro por rango de numeración
        if ($request->filled('number_from')) {
            $query->whereHas('journalEntry', function($q) use ($request) {
                $q->where('number', '>=', $request->number_from);
            });
        }
        if ($request->filled('number_to')) {
            $query->whereHas('journalEntry', function($q) use ($request) {
                $q->where('number', '<=', $request->number_to);
            });
        }

        $details = $query->orderBy('id', 'asc')->get();

        // Agrupar por asiento (prefijo + número)
        $grouped = [];
        foreach ($details as $detail) {
            $entry = $detail->journalEntry;
            $key = $entry->journal_prefix->prefix . '-' . $entry->number;
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'prefijo_numero' => $key,
                    'fecha' => $entry->date,
                    'concepto' => $entry->description,
                    'detalles' => [],
                    'total_debito' => 0,
                    'total_credito' => 0,
                ];
            }
            // Obtener tercero implicado SIEMPRE
            $thirdParty = $detail->thirdParty;
            if ($thirdParty && isset($thirdParty->name)) {
                $thirdPartyName = $thirdParty->name;
            } else {
                $tp = $this->getThirdPartyFromEntry($entry);
                $thirdPartyName = isset($tp['name']) ? $tp['name'] : '-';
            }

            // Agrega el nombre y número del tercero al detalle
            $detail->third_party_name = $thirdPartyName;

            $grouped[$key]['detalles'][] = $detail;
            $grouped[$key]['total_debito'] += $detail->debit;
            $grouped[$key]['total_credito'] += $detail->credit;
        }

        $html = view('accounting::pdf.journal_entry_details_report', [
            'groups' => $grouped,
            'filters' => $request->all(),
        ])->render();

        $mpdf = new Mpdf(['orientation' => 'L']);
        $mpdf->SetHeader('Reporte de Detalles Contables');
        $mpdf->SetFooter('Generado el ' . now()->format('Y-m-d H:i:s'));
        $mpdf->WriteHTML($html);

        return $mpdf->Output('reporte_detalles_contables.pdf', 'I');
    }
    private function getThirdPartyFromEntry($entry)
    {
        // Tercero directo
        if ($entry->thirdParty && isset($entry->thirdParty->name)) {
            return ['name' => $entry->thirdParty->name];
        }
        // Compra
        if ($entry->purchase && $entry->purchase->supplier && isset($entry->purchase->supplier->name)) {
            return ['name' => $entry->purchase->supplier->name];
        }
        // Documento de venta
        if ($entry->document && $entry->document->customer && isset($entry->document->customer->name)) {
            return ['name' => $entry->document->customer->name];
        }
        // Documento POS
        if ($entry->document_pos && $entry->document_pos->customer && isset($entry->document_pos->customer->name)) {
            return ['name' => $entry->document_pos->customer->name];
        }
        // Documento de soporte
        if ($entry->support_document && $entry->support_document->supplier && isset($entry->support_document->supplier->name)) {
            return ['name' => $entry->support_document->supplier->name];
        }
        // Nómina
        if ($entry->document_payroll && $entry->document_payroll->worker && (isset($entry->document_payroll->worker->full_name) || isset($entry->document_payroll->worker->name))) {
            return ['name' => $entry->document_payroll->worker->full_name ?? $entry->document_payroll->worker->name];
        }
        // Nota de ajuste de soporte
        if ($entry->support_document_adjust_note && $entry->support_document_adjust_note->supplier && isset($entry->support_document_adjust_note->supplier->name)) {
            return ['name' => $entry->support_document_adjust_note->supplier->name];
        }
        return ['name' => '-'];
    }
}