<?php

namespace Modules\Accounting\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounting\Models\ChartOfAccount;
use Modules\Accounting\Models\JournalEntryDetail;
use Maatwebsite\Excel\Facades\Excel;
use Mpdf\Mpdf;
use Modules\Accounting\Exports\GeneralLedgerExport;
use Modules\Factcolombia1\Models\Tenant\Company;

class ReportGeneralLedgerController extends Controller
{
    public function index()
    {
        return view('accounting::reports.general_ledger');
    }

    /**
     * Retorna los movimientos agrupados para el libro mayor y balance.
     */
    public function records(Request $request)
    {
        $date_start = $request->date_start;
        $date_end = $request->date_end;

        $filter_account = $request->filterAccount;
        $filter_level = $request->filterLevel;
        $filter_type = $request->filterType;
        $filter_code_from = $request->filterCodeFrom;
        $filter_code_to = $request->filterCodeTo;

        $accounts = ChartOfAccount::query()
            ->when($filter_level, function ($q) use ($filter_level) {
                $q->where('level', $filter_level);
            })
            ->when($filter_type, function ($q) use ($filter_type) {
                $q->where('type', $filter_type);
            })
            ->when($filter_account, function ($q) use ($filter_account) {
                $q->where('code', $filter_account);
            })
            ->when(($filter_code_from && $filter_code_to), function ($q) use ($filter_code_from, $filter_code_to) {
                $q->whereBetween('code', [$filter_code_from, $filter_code_to]);
            })
            ->orderBy('code')
            ->get();

        $results = [];

        foreach ($accounts as $acc) {

            // SALDO INICIAL
            $initial = JournalEntryDetail::selectRaw("SUM(debit) AS debit, SUM(credit) AS credit")
                ->where('chart_of_account_id', $acc->id)
                ->whereHas('journalEntry', function ($q) use ($date_start) {
                    $q->where('date', '<', $date_start)->where('status', 'posted');
                })
                ->first();

            $saldo_inicial = ($initial->debit - $initial->credit);

            // MOVIMIENTOS DEL PERIODO
            $movements = JournalEntryDetail::with(['journalEntry'])
                ->where('chart_of_account_id', $acc->id)
                ->whereHas('journalEntry', function ($q) use ($date_start, $date_end) {
                    $q->whereBetween('date', [$date_start, $date_end])
                    ->where('status', 'posted');
                })
                ->orderByRaw("journal_entry_id ASC")
                ->get();

            $saldo = $saldo_inicial;
            $movement_rows = [];

            foreach ($movements as $m) {
                $saldo = $saldo + $m->debit - $m->credit;

                $movement_rows[] = [
                    'id' => $m->id,
                    'date' => $m->journalEntry->date,
                    'document' => $m->journalEntry->getRelatedComprobanteNumber(),
                    'detail' => $m->journalEntry->description,
                    'debit' => (float)$m->debit,
                    'credit' => (float)$m->credit,
                    'balance' => $saldo,
                ];
            }

            $results[] = [
                'code' => $acc->code,
                'name' => $acc->name,
                'type' => $acc->type,
                'level' => $acc->level,
                'saldo_inicial' => $saldo_inicial,
                'movements' => $movement_rows,
                'saldo_final' => $saldo,
            ];
        }
        if ($request->hideEmpty) {
            $results = array_values(array_filter($results, function($acc){
                return count($acc['movements']) > 0;
            }));
        }

        return response()->json(['data' => $results], 200);
    }

    /**
     * Exporta el reporte a PDF o Excel
     */
    public function export(Request $request)
    {
        $format  = $request->input('format'); // pdf | excel

        $company = Company::first();
        
        $records = $this->records($request)->getData()->data;

        $filename = 'Libro_Mayor_y_Balance_' . date('YmdHis');

        // --- Excel ---
        if ($format === 'excel') {
            return Excel::download(new GeneralLedgerExport(
                    $records,
                    $company,
                    $request->date_start,
                    $request->date_end
                ),
                "{$filename}.xlsx"
            );
        }

        // --- PDF ---
        $html = view('accounting::pdf.general_ledger_pdf', [
            'records' => $records,
            'request' => $request,
            'company' => $company
        ])->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_top' => 15,
            'margin_bottom' => 15,
            'margin_left' => 10,
            'margin_right' => 10,
        ]);

        $mpdf->SetHeader('Libro Mayor y Balance');
        $mpdf->SetFooter('Generado el ' . now()->format('Y-m-d H:i:s'));

        $mpdf->WriteHTML($html);

        return response($mpdf->Output($filename . '.pdf', 'I'))
                ->header('Content-Type', 'application/pdf');
    }

    /**
     * Retorna solo las cuentas para el select (id, code, name)
     */
    public function listAccounts(Request $request)
    {
        $search = trim($request->search);

        $query = ChartOfAccount::query()
            ->orderBy('code');

        if ($search !== '') {

            // Si es un número → buscar SOLO por código
            if (preg_match('/^[0-9.]+$/', $search)) {
                $query->where('code', 'like', $search.'%');
            } 
            else {
                // Si contiene letras → buscar por nombre
                $query->where('name', 'like', '%'.$search.'%');
            }
        }

        $accounts = $query->limit(50)->get(['id', 'code', 'name', 'level', 'type']);

        return response()->json($accounts);
    }
}