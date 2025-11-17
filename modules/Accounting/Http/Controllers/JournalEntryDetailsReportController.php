<?php

namespace Modules\Accounting\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounting\Models\JournalEntryDetail;
use Modules\Accounting\Models\JournalEntry;
use Modules\Factcolombia1\Models\Tenant\Company;
use Modules\Accounting\Exports\JournalEntryDetailsExport;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Modules\Factcolombia1\Models\Tenant\TypeIdentityDocument;
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
        $report = $this->getReportData($request);

        return [
            'data' => $report['entries'],
            'meta' => [
                'total' => count($report['entries']),
                'total_debit' => $report['totalDebit'],
                'total_credit' => $report['totalCredit'],
            ],
        ];
    }

    private function getReportData(Request $request)
    {
        $query = JournalEntry::with([
            'journal_prefix',
            'details.chartOfAccount',
            'details.thirdParty',
            'document.person',
            'purchase.supplier',
            'support_document.person',
            'document_pos.person',
            'document_payroll.worker',
        ]);

        if ($request->filled('month')) {
            $query->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$request->month]);
        }

        if ($request->filled('date_start') && $request->filled('date_end')) {
            $query->whereBetween('date', [$request->date_start, $request->date_end]);
        }

        if ($request->filled('journal_prefix_id')) {
            $query->where('journal_prefix_id', $request->journal_prefix_id);
        }

        if ($request->filled('number_from')) {
            $query->where('number', '>=', $request->number_from);
        }
        if ($request->filled('number_to')) {
            $query->where('number', '<=', $request->number_to);
        }

        $entries = $query
            ->orderBy('date')
            ->orderBy('journal_prefix_id')
            ->orderBy('number')
            ->get();

        $entryIds = $entries->pluck('id');

        $totalDebit = JournalEntryDetail::whereIn('journal_entry_id', $entryIds)->sum('debit');
        $totalCredit = JournalEntryDetail::whereIn('journal_entry_id', $entryIds)->sum('credit');

        $data = [];

        foreach ($entries as $entry) {

            $detailsArr = [];
            $entryDebit = 0;
            $entryCredit = 0;

            foreach ($entry->details as $detail) {
                $thirdParty = $this->resolveThirdPartyForDetail($detail, $entry);

                $detailsArr[] = [
                    'account_code' => $detail->chartOfAccount->code ?? null,
                    'account_name' => $detail->chartOfAccount->name ?? null,
                    'third_party_name' => $thirdParty,
                    'debit' => floatval($detail->debit),
                    'credit' => floatval($detail->credit),
                ];

                $entryDebit += floatval($detail->debit);
                $entryCredit += floatval($detail->credit);
            }

            $data[] = [
                'id' => $entry->id,
                'date' => $entry->date,
                'prefix' => $entry->journal_prefix->prefix,
                'number' => $entry->number,
                'description' => $entry->description,
                'total_debit' => $entryDebit,
                'total_credit' => $entryCredit,
                'details' => $detailsArr,
            ];
        }

        return [
            'entries' => $data,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
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
        $report = $this->getReportData($request);

        $company = Company::first();

        $html = view('accounting::pdf.journal_entry_details_report', [
            'entries' => $report['entries'],
            'total_debit' => $report['totalDebit'],
            'total_credit' => $report['totalCredit'],
            'company' => $company,
            'filters' => $request->all(),
        ])->render();

        $mpdf = new Mpdf(['orientation' => 'L']);
        $mpdf->SetHeader('Reporte de Detalles Contables');
        $mpdf->SetFooter('Generado el ' . now()->format('Y-m-d H:i:s'));
        $mpdf->WriteHTML($html);

        return $mpdf->Output('libro_diario.pdf', 'I');
    }
    private function resolveThirdPartyForDetail($detail, $entry)
    {

        if ($detail->thirdParty && isset($detail->thirdParty->name)) {
            return $detail->thirdParty->name;
        }

        // DOCUMENTO DE VENTA
        if ($entry->document && $entry->document->person && isset($entry->document->person->name)) {
            return $entry->document->person->name;
        }

        // COMPRA
        if ($entry->purchase && $entry->purchase->supplier && isset($entry->purchase->supplier->name)) {
            return $entry->purchase->supplier->name;
        }

        // DOCUMENTO POS
        if ($entry->document_pos && $entry->document_pos->customer && isset($entry->document_pos->customer->name)) {
            return $entry->document_pos->customer->name;
        }

        // DOCUMENTO SOPORTE
        if ($entry->support_document && $entry->support_document->supplier && isset($entry->support_document->supplier->name)) {
            return $entry->support_document->supplier->name;
        }

        // NÓMINA
        if ($entry->document_payroll) {
            if ($entry->document_payroll->model_worker) {
                $w = $entry->document_payroll->model_worker;
                return $w->full_name ?? $w->name ?? '-';
            }

            if ($entry->document_payroll->worker) {
                $wj = $entry->document_payroll->worker;
                return $wj->full_name ?? $wj->name ?? '-';
            }
        }
        return '-';
    }

    public function exportExcel(Request $request)
    {
        $report = $this->getReportData($request);
        $company = Company::first();
        return Excel::download(
            new JournalEntryDetailsExport(
                $report['entries'],
                $company,
                $request->all(),
                $report['totalDebit'],
                $report['totalCredit']
            ),
            'libro_diario.xlsx'
        );
    }
}