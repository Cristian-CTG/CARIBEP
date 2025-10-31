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
        $month = $request->input('month'); // formato yyyy-MM
        $bank_account_id = $request->input('bank_account_id');
        $auxiliar = $request->input('auxiliar');

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

        // Calcular saldo inicial como la suma de todos los movimientos hasta el último día del mes anterior
        $prev_end = date("Y-m-t", strtotime('-1 month', strtotime($start_date)));

        if ($bank_account_id === 'cash') {
            // Solo caja: auxiliar 110505
            $saldo_inicial_query = JournalEntryDetail::whereHas('chartOfAccount', function($q) {
                    $q->where('code', '110505');
                })
                ->whereHas('journalEntry', function($q) use ($prev_end) {
                    $q->where('date', '<=', $prev_end)
                    ->where('status', 'posted');
                })
                ->get();
        } else {
            // Solo banco: auxiliar del banco y bank_account_id igual al seleccionado
            $saldo_inicial_query = JournalEntryDetail::where('chart_of_account_id', $bank_account->chart_of_account_id)
                ->where('bank_account_id', $bank_account->id)
                ->whereHas('journalEntry', function($q) use ($prev_end) {
                    $q->where('date', '<=', $prev_end)
                    ->where('status', 'posted');
                })
                ->get();
        }

        $saldo_inicial = $saldo_inicial_query->sum('debit') - $saldo_inicial_query->sum('credit');

        // 2. Filtrar detalles de asientos por cuenta bancaria/caja y auxiliar (solo del mes actual)
        $query = JournalEntryDetail::with(['journalEntry', 'chartOfAccount'])
            ->whereHas('journalEntry', function($q) use ($start_date, $end_date) {
                $q->whereBetween('date', [$start_date, $end_date])
                ->where('status', 'posted');
            });

        if ($bank_account_id === 'cash') {
            // Solo caja: auxiliar 110505
            $query->whereHas('chartOfAccount', function($q) {
                $q->where('code', '110505');
            });
        } else {
            // Solo banco: auxiliar del banco y bank_account_id igual al seleccionado
            $query->where('chart_of_account_id', $bank_account->chart_of_account_id)
                ->where('bank_account_id', $bank_account->id);
        }

        $details = $query->orderBy('id')->get();

        // Calcular saldo final del mes actual
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

    public function preview(Request $request)
    {
        $month = $request->input('month'); // formato yyyy-MM
        $bank_account_id = $request->input('bank_account_id');
        $auxiliar = $request->input('auxiliar');
        $page = (int)$request->input('page', 1);
        $perPage = (int)$request->input('per_page', 10);

        $start_date = $month . '-01';
        $end_date = date("Y-m-t", strtotime($start_date));

        // Buscar la cuenta bancaria y su cuenta contable asociada
        $bank_account = null;
        if ($bank_account_id !== 'cash') {
            $bank_account = BankAccount::with('chart_of_account')->find($bank_account_id);
            // Si no existe el banco, retorna vacío
            if (!$bank_account) {
                return response()->json([
                    'preview' => [],
                    'saldo_inicial' => '0,00',
                    'saldo_final' => '0,00',
                    'pagination' => [
                        'total' => 0,
                        'per_page' => $perPage,
                        'current_page' => $page,
                        'last_page' => 1,
                    ]
                ]);
            }
        }

        // Calcular saldo inicial como la suma de todos los movimientos hasta el último día del mes anterior
        $prev_end = date("Y-m-t", strtotime('-1 month', strtotime($start_date)));

        if ($bank_account_id === 'cash') {
            $saldo_inicial_query = JournalEntryDetail::whereHas('chartOfAccount', function($q) {
                    $q->where('code', '110505');
                })
                ->whereHas('journalEntry', function($q) use ($prev_end) {
                    $q->where('date', '<=', $prev_end)
                    ->where('status', 'posted');
                });
        } else {
            $saldo_inicial_query = JournalEntryDetail::where('chart_of_account_id', $bank_account->chart_of_account_id)
                ->where('bank_account_id', $bank_account->id)
                ->whereHas('journalEntry', function($q) use ($prev_end) {
                    $q->where('date', '<=', $prev_end)
                    ->where('status', 'posted');
                });
        }

        $saldo_inicial = $saldo_inicial_query->sum('debit') - $saldo_inicial_query->sum('credit');

        // Movimientos del mes actual (para saldo final, sin paginación)
        $query_full = JournalEntryDetail::with(['journalEntry', 'chartOfAccount'])
            ->whereHas('journalEntry', function($q) use ($start_date, $end_date) {
                $q->whereBetween('date', [$start_date, $end_date])
                ->where('status', 'posted');
            });

        if ($bank_account_id === 'cash') {
            $query_full->whereHas('chartOfAccount', function($q) {
                $q->where('code', '110505');
            });
        } else {
            $query_full->where('chart_of_account_id', $bank_account->chart_of_account_id)
                ->where('bank_account_id', $bank_account->id);
        }

        $saldo_final = $saldo_inicial + $query_full->get()->sum(function($d) {
            return $d->debit - $d->credit;
        });

        // Movimientos paginados para la tabla
        $query = clone $query_full;
        $total = $query->count();
        $details = $query->orderBy('id')->skip(($page - 1) * $perPage)->take($perPage)->get();

        // Construir la respuesta para la tabla de vista previa
        $preview = [];
        $saldo = $saldo_inicial + $query->orderBy('id')->take(($page - 1) * $perPage)->get()->sum(function($d) {
            return $d->debit - $d->credit;
        });

        $total_debito = $query_full->sum('debit');
        $total_credito = $query_full->sum('credit');

        foreach ($details as $detail) {
            $entry = $detail->journalEntry;
            $debit = $detail->debit ?? 0;
            $credit = $detail->credit ?? 0;
            $saldo += $debit - $credit;

            $type = '-';
            if ($debit > 0) $type = 'CI';
            if ($credit > 0) $type = 'CE';

            $document = $entry && $entry->journal_prefix ? ($entry->journal_prefix->prefix ?? '') . '-' . ($entry->number ?? '') : '';
            $payment_method = $detail->payment_method_name ?? 'TRANSFERENCIA';

            $is_refund = false;
            if ($entry && $entry->description && (stripos($entry->description, 'devolución') !== false || stripos($entry->description, 'nota de crédito') !== false)) {
                $is_refund = true;
            }
            if ($is_refund) $payment_method = 'DEVOLUCIÓN';

            $third_party_document = $detail->thirdParty ? $detail->thirdParty->document : '';
            $third_party_name = $detail->thirdParty ? $detail->thirdParty->name : '';

            $preview[] = [
                'date' => $entry->date ?? '',
                'document' => $document,
                'payment_method' => $payment_method,
                'description' => $entry->description ?? '',
                'type' => $type,
                'debit' => number_format($debit, 2, ',', '.'),
                'credit' => number_format($credit, 2, ',', '.'),
                'balance' => number_format($saldo, 2, ',', '.'),
                'third_party_document' => $third_party_document,
                'third_party_name' => $third_party_name,
            ];
        }

        return response()->json([
            'preview' => $preview,
            'saldo_inicial' => number_format($saldo_inicial, 2, ',', '.'),
            'saldo_final' => number_format($saldo_final, 2, ',', '.'),
            'total_debito' => number_format($total_debito, 2, ',', '.'),
            'total_credito' => number_format($total_credito, 2, ',', '.'),
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
            ]
        ]);
    }
}