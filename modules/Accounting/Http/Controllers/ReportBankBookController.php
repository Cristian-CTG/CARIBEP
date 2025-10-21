<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade as PDF;
use App\Models\Tenant\Company;
use App\Models\Tenant\BankAccount;
use Modules\Accounting\Models\ChartOfAccount;
use Modules\Accounting\Models\JournalEntryDetail;
use Modules\Accounting\Models\JournalEntry;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Accounting\Exports\BankBookExport;

class ReportBankBookController extends Controller
{
    public function index()
    {
        return view('accounting::reports.bank_book');
    }

    /**
     * Exporta el libro de bancos a PDF o Excel
     */
    public function export(Request $request)
    {
        // Filtros
        $month = $request->input('month'); // formato yyyy-MM
        $bank_account_id = $request->input('bank_account_id');
        $auxiliar = $request->input('auxiliar');

        // Fechas del mes
        $start_date = $month . '-01';
        $end_date = date("Y-m-t", strtotime($start_date));

        // Buscar la cuenta bancaria y su cuenta contable asociada
        $bank_account = null;
        if ($bank_account_id !== 'cash') {
            $bank_account = BankAccount::with('chart_of_account')->find($bank_account_id);
        }

        // Si se filtra por auxiliar, buscar la cuenta contable
        $chart_account = null;
        if ($auxiliar) {
            $chart_account = ChartOfAccount::where('code', $auxiliar)->first();
        }

        // 1. Sumar asientos de saldo inicial (prefijo id 11)
        $si_query = JournalEntryDetail::where(function($q) use ($bank_account, $chart_account, $bank_account_id) {
                if ($bank_account_id === 'cash') {
                    // Solo caja
                    if ($chart_account) {
                        $q->where('chart_of_account_id', $chart_account->id);
                    }
                } else {
                    if ($bank_account && $bank_account->chart_of_account_id) {
                        $q->where('chart_of_account_id', $bank_account->chart_of_account_id);
                    }
                    if ($chart_account) {
                        $q->where('chart_of_account_id', $chart_account->id);
                    }
                }
            })
            ->whereHas('journalEntry', function($q) {
                $q->where('journal_prefix_id', 11)
                ->where('status', 'posted');
            })
            ->get();

        $saldo_inicial = $si_query->sum('debit') - $si_query->sum('credit');

        // 2. Sumar movimientos anteriores al mes (excepto saldo inicial)
        $saldo_query = JournalEntryDetail::where(function($q) use ($bank_account, $chart_account, $bank_account_id) {
                if ($bank_account_id === 'cash') {
                    if ($chart_account) {
                        $q->where('chart_of_account_id', $chart_account->id);
                    }
                } else {
                    if ($bank_account && $bank_account->chart_of_account_id) {
                        $q->where('chart_of_account_id', $bank_account->chart_of_account_id);
                    }
                    if ($chart_account) {
                        $q->where('chart_of_account_id', $chart_account->id);
                    }
                }
            })
            ->whereHas('journalEntry', function($q) use ($start_date) {
                $q->where('date', '<', $start_date)
                ->where('status', 'posted')
                ->where('journal_prefix_id', '!=', 11);
            })
            ->get();

        $saldo_inicial += $saldo_query->sum('debit') - $saldo_query->sum('credit');

        // Filtrar detalles de asientos por cuenta bancaria y auxiliar (excluyendo saldo inicial)
        $query = JournalEntryDetail::with(['journalEntry', 'chartOfAccount'])
            ->whereHas('journalEntry', function($q) use ($start_date, $end_date) {
                $q->whereBetween('date', [$start_date, $end_date])
                ->where('status', 'posted')
                ->where('journal_prefix_id', '!=', 11);
            });

        if ($bank_account_id === 'cash') {
            // Solo caja: auxiliar 110505
            $query->whereHas('chartOfAccount', function($q) {
                $q->where('code', '110505');
            });
        } else {
            // Solo banco: auxiliar del banco y bank_account_id igual al seleccionado
            if ($bank_account && $bank_account->chart_of_account_id) {
                $query->where('chart_of_account_id', $bank_account->chart_of_account_id)
                    ->where('bank_account_id', $bank_account->id);
            }
        }

        $details = $query->orderBy('id')->get();

        // Calcular saldo final
        $saldo_final = $saldo_inicial;
        foreach ($details as $detail) {
            $saldo_final += $detail->debit - $detail->credit;
        }

        // Datos para la vista
        $company = Company::first();
        $filters = [
            'month' => $month,
            'bank_account' => $bank_account,
            'auxiliar' => $auxiliar,
        ];

        $report_data = compact('details', 'company', 'filters', 'saldo_inicial', 'saldo_final');

        // Exportar
        $type = $request->input('type', 'pdf');
        if ($type === 'excel') {
            return Excel::download(new BankBookExport($report_data), 'Libro_Bancos_'.date('YmdHis').'.xlsx');
        } else {
            $pdf = PDF::loadView('accounting::reports.bank_book_pdf', $report_data)->setPaper('a4', 'landscape');
            $filename = 'Libro_Bancos_'.date('YmdHis');
            return $pdf->stream($filename.'.pdf');
        }
    }
    public function records(Request $request)
    {
        // Obtiene todas las cuentas bancarias activas
        $bankAccounts = BankAccount::where('status', 1)
            ->with('bank', 'currency', 'chart_of_account')
            ->get();

        return response()->json($bankAccounts);
    }
}