<?php

namespace Modules\Accounting\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Factcolombia1\Models\Tenant\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\ChartOfAccount;
use Modules\Accounting\Models\JournalEntryDetail;
use Carbon\Carbon;
use Mpdf\Mpdf;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Accounting\Exports\TrialBalanceExport;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportTrialBalanceController extends Controller
{
    public function index()
    {
        return view('accounting::reports.trial_balance');
    }

    public function records(Request $request)
    {
        $dateStart = Carbon::parse($request->get('date_start'))->startOfDay();
        $dateEnd   = Carbon::parse($request->get('date_end'))->endOfDay();

        $accounts = ChartOfAccount::where('status', 1);

        // Filtros de nivel y tipo
        if ($request->filled('filterLevel')) {
            $accounts->where('level', $request->get('filterLevel'));
        }
        if ($request->filled('filterType')) {
            $accounts->where('type', $request->get('filterType'));
        }

        // Filtro por rango de código
        if ($request->filled('filterCodeFrom') && $request->filled('filterCodeTo')) {
            $accounts->whereBetween('code', [$request->get('filterCodeFrom'), $request->get('filterCodeTo')]);
        }

        $accounts = $accounts->orderBy('code')->get();

        $rows = [];
        $total_debitos = $total_creditos = $total_saldo_final = 0;
        $total_saldo_inicial = 0;

        foreach ($accounts as $account) {
            $saldo_inicial = JournalEntryDetail::where('chart_of_account_id', $account->id)
                ->whereHas('journalEntry', function ($q) use ($dateStart) {
                    $q->where('date', '<', $dateStart);
                })
                ->selectRaw('COALESCE(SUM(debit - credit), 0) as saldo')
                ->value('saldo');

            $debitos = JournalEntryDetail::where('chart_of_account_id', $account->id)
                ->whereHas('journalEntry', function ($q) use ($dateStart, $dateEnd) {
                    $q->whereBetween('date', [$dateStart, $dateEnd]);
                })
                ->sum('debit');

            $creditos = JournalEntryDetail::where('chart_of_account_id', $account->id)
                ->whereHas('journalEntry', function ($q) use ($dateStart, $dateEnd) {
                    $q->whereBetween('date', [$dateStart, $dateEnd]);
                })
                ->sum('credit');

            $saldo_final = $saldo_inicial + $debitos - $creditos;

            if ($request->has('hideZeroMovements') && ($request->get('hideZeroMovements') == '1' || $request->get('hideZeroMovements') == 'true') && $debitos == 0 && $creditos == 0) {
                continue;
            }

            // if ($request->boolean('hideZeroFinal') && $saldo_final == 0) {
            //     continue;
            // }

            $rows[] = [
                'code' => $account->code,
                'name' => $account->name,
                'level' => $account->level,
                'type' => $account->type,
                'saldo_inicial' => (float)$saldo_inicial,
                'debitos' => (float)$debitos,
                'creditos' => (float)$creditos,
                'saldo_final' => (float)$saldo_final,
            ];

            $total_debitos += $debitos;
            $total_creditos += $creditos;
            $total_saldo_inicial += $saldo_inicial;
            $total_saldo_final += $saldo_final;
        }

        return [
            'data' => $rows,
            'totals' => [
                'debitos' => $total_debitos,
                'creditos' => $total_creditos,
                'saldo_final' => $total_saldo_final,
                'saldo_inicial' => $total_saldo_inicial
            ],
        ];
    }

    public function export(Request $request)
    {
        $format = $request->get('format');
        $result = $this->records($request);
        $data = $result['data'];
        $totals = $result['totals'];

        if ($format === 'pdf') {
            return $this->exportPdf($data, $totals, $request);
        }

        if ($format === 'excel') {
            return $this->exportExcel($data, $totals);
        }

        abort(404);
    }

    private function exportPdf($data, $totals, $request)
    {
        $dateStart = $request->get('date_start');
        $dateEnd = $request->get('date_end');
        $company = Company::first();

        $html = view('accounting::pdf.trial_balance_pdf', compact('data', 'totals', 'dateStart', 'dateEnd', 'company'))->render();

        $mpdf = new Mpdf(['format' => 'A4-L']);
        $mpdf->WriteHTML($html);
        return $mpdf->Output("BalanceDePrueba_{$dateStart}_{$dateEnd}.pdf", 'I');
    }

    private function exportExcel($data, $totals)
    {
        $company = Company::first();
        $dateStart = request()->get('date_start');
        $dateEnd = request()->get('date_end');

        return Excel::download(
            new TrialBalanceExport($data, $totals, $company, $dateStart, $dateEnd),
            'BalanceDePrueba_' . date('Ymd_His') . '.xlsx'
        );
    }
}
