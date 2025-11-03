<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade as PDF;
use App\Models\Tenant\BankAccount;
use Modules\Factcolombia1\Models\Tenant\Company;
use Modules\Accounting\Models\ChartOfAccount;
use Modules\Accounting\Models\JournalEntryDetail;
use Modules\Accounting\Models\BankReconciliation;
use Modules\Accounting\Models\BankReconciliationDetail;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Mpdf\Mpdf;

class BankReconciliationController extends Controller
{
    public function index()
    {
        return view('accounting::bank_reconciliation.index');
    }

    public function columns()
    {
        return [
            'date' => 'Fecha de Creación',
            'month' => 'Mes de Conciliación',
            'bank_account' => 'Cuenta Bancaria',
        ];
    }

    public function bankAccounts()
    {
        // Devuelve todas las cuentas bancarias activas con su banco relacionado
        $bankAccounts = BankAccount::where('status', 1)
            ->with('bank')
            ->get();

        return response()->json($bankAccounts);
    }

    public function records(Request $request)
    {
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);

        $query = BankReconciliation::with('bankAccount');

        $this->Filters($request, $query);

        // Puedes agregar filtros aquí si lo necesitas

        $reconciliations = $query->orderBy('created_at', 'desc')->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            "data" => $reconciliations->items(),
            "links" => [
                "first" => $reconciliations->url(1),
                "last" => $reconciliations->url($reconciliations->lastPage()),
                "prev" => $reconciliations->previousPageUrl(),
                "next" => $reconciliations->nextPageUrl(),
            ],
            "meta" => [
                "current_page" => $reconciliations->currentPage(),
                "from" => $reconciliations->firstItem(),
                "last_page" => $reconciliations->lastPage(),
                "path" => request()->url(),
                "per_page" => (string) $reconciliations->perPage(),
                "to" => $reconciliations->lastItem(),
                "total" => $reconciliations->total(),
            ]
        ]);
    }
    public function edit($id)
    {
        $reconciliation = BankReconciliation::with(['bankAccount', 'details'])->findOrFail($id);

        // Separa detalles en mas/menos
        $detalles_mas = $reconciliation->details->where('type', 'entrance')->values();
        $detalles_menos = $reconciliation->details->where('type', 'exit')->values();

        return response()->json([
            'id' => $reconciliation->id,
            'bank_account_id' => $reconciliation->bank_account_id,
            'month' => $reconciliation->month,
            'date' => $reconciliation->date,
            'saldo_extracto' => $reconciliation->saldo_extracto,
            'detalles_mas' => $detalles_mas,
            'detalles_menos' => $detalles_menos,
            // Puedes agregar movimientos y seleccionados si lo necesitas
        ]);
    }

    public function Filters(Request $request, $query)
    {
       // Filtro por fecha de creación (rango)
        if ($request->filled('column') && $request->input('column') == 'daterange' && $request->filled('value')) {
            $dates = explode('_', $request->input('value'));
            if (count($dates) == 2) {
                $query->whereBetween('date', [$dates[0], $dates[1]]);
            }
        }

        // Filtro por mes
        if ($request->filled('month')) {
            $query->where('month', $request->input('month'));
        }

        // Filtro por cuenta bancaria
        if ($request->filled('bank_account_id')) {
            $query->where('bank_account_id', (int)$request->input('bank_account_id'));
        }
    }
    public function destroy($id)
    {
        $reconciliation = BankReconciliation::findOrFail($id);

        // Elimina los detalles asociados
        $reconciliation->details()->delete();

        // Elimina la conciliación
        $reconciliation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Conciliación eliminada correctamente.'
        ]);
    }

    public function movements(Request $request)
    {
        $month = $request->input('month'); // formato yyyy-MM
        $bank_account_id = $request->input('bank_account_id');
        $page = (int)$request->input('page', 1);
        $perPage = (int)$request->input('per_page', 15);

        $start_date = $month . '-01';
        $end_date = date("Y-m-t", strtotime($start_date));

        // Buscar la cuenta bancaria y su cuenta contable asociada
        $bank_account = BankAccount::with('chart_of_account')->find($bank_account_id);
        if (!$bank_account) {
            return response()->json([
                'movements' => [],
                'pagination' => [
                    'total' => 0,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'last_page' => 1,
                ]
            ]);
        }

        // Query base
        $query = JournalEntryDetail::with(['journalEntry', 'chartOfAccount', 'thirdParty'])
            ->where('chart_of_account_id', $bank_account->chart_of_account_id)
            ->where('bank_account_id', $bank_account->id)
            ->whereHas('journalEntry', function($q) use ($start_date, $end_date) {
                $q->whereBetween('date', [$start_date, $end_date])
                ->where('status', 'posted');
            });
        
        $total_debit = (clone $query)->sum('debit');
        $total_credit = (clone $query)->sum('credit');

        $total = $query->count();
        $details = $query->orderBy('id')->skip(($page - 1) * $perPage)->take($perPage)->get();

        $movements = [];
        foreach ($details as $detail) {
            $entry = $detail->journalEntry;
            $movements[] = [
                'id' => $detail->id, // ID único para selección
                'date' => $entry->date ?? '',
                'document' => $entry && $entry->journal_prefix ? ($entry->journal_prefix->prefix ?? '') . '-' . ($entry->number ?? '') : '',
                'payment_method' => $detail->payment_method_name ?? 'TRANSFERENCIA',
                'description' => $entry->description ?? '',
                'debit' => number_format($detail->debit ?? 0, 2, ',', '.'),
                'credit' => number_format($detail->credit ?? 0, 2, ',', '.'),
                'third_party_document' => $detail->thirdParty ? $detail->thirdParty->document : '',
                'third_party_name' => $detail->thirdParty ? $detail->thirdParty->name : '',
                'third_party_type' => $detail->thirdParty ? $detail->thirdParty->getTypeName() : '',
                'cheque_number' => $detail->cheque_number ?? '',
            ];
        }

        return response()->json([
            'movements' => $movements,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
            ],
            'totals' => [
                'debit' => $total_debit,
                'credit' => $total_credit,
            ]
        ]);
    }

    public function export(Request $request)
    {
        $month = $request->input('month'); // formato yyyy-MM
        $bank_account_id = $request->input('bank_account_id');

        $start_date = $month . '-01';
        $end_date = date("Y-m-t", strtotime($start_date));

        $bank_account = BankAccount::with('chart_of_account')->find($bank_account_id);
        $auxiliar = $bank_account && $bank_account->chart_of_account ? $bank_account->chart_of_account->code : null;
        $chart_account = $auxiliar ? ChartOfAccount::where('code', $auxiliar)->first() : null;

        // Saldo inicial igual que libro de bancos
        $saldo_inicial = 0;
        if ($chart_account) {
            $si_query = JournalEntryDetail::where('chart_of_account_id', $chart_account->id)
                ->where('bank_account_id', $bank_account->id) // <-- AQUÍ FILTRAS POR BANCO
                ->whereHas('journalEntry', function($q) {
                    $q->where('journal_prefix_id', 11)
                    ->where('status', 'posted');
                })
                ->get();
            $saldo_inicial = $si_query->sum('debit') - $si_query->sum('credit');

            $saldo_query = JournalEntryDetail::where('chart_of_account_id', $chart_account->id)
                ->where('bank_account_id', $bank_account->id) // <-- AQUÍ FILTRAS POR BANCO
                ->whereHas('journalEntry', function($q) use ($start_date) {
                    $q->where('date', '<', $start_date)
                    ->where('status', 'posted')
                    ->where('journal_prefix_id', '!=', 11);
                })
                ->get();
            $saldo_inicial += $saldo_query->sum('debit') - $saldo_query->sum('credit');
        }

        // Solo traslados a favor (DB): ingresos asociados al banco en el mes
        $traslado_a_favor = 0;
        if ($chart_account) {
            $traslado_a_favor_query = JournalEntryDetail::where('chart_of_account_id', $chart_account->id)
                ->where('bank_account_id', $bank_account->id)
                ->whereHas('journalEntry', function($q) use ($start_date, $end_date) {
                    $q->whereBetween('date', [$start_date, $end_date])
                    ->where('status', 'posted')
                    ->where('journal_prefix_id', '!=', 11);
                })
                ->get();
            $traslado_a_favor = $traslado_a_favor_query->sum('debit');
        }

        // Traslados en contra (CR): egresos asociados al banco en el mes
        $traslados_en_contra = 0;
        if ($chart_account) {
            $traslados_en_contra_query = JournalEntryDetail::where('chart_of_account_id', $chart_account->id)
                ->where('bank_account_id', $bank_account->id)
                ->whereHas('journalEntry', function($q) use ($start_date, $end_date) {
                    $q->whereBetween('date', [$start_date, $end_date])
                    ->where('status', 'posted');
                })
                ->get();
            $traslados_en_contra = $traslados_en_contra_query->sum('credit');
        }

        // Obtener los detalles del mes para calcular el saldo final
        $details_query = JournalEntryDetail::where('chart_of_account_id', $chart_account->id)
            ->where('bank_account_id', $bank_account->id)
            ->whereHas('journalEntry', function($q) use ($start_date, $end_date) {
                $q->whereBetween('date', [$start_date, $end_date])
                ->where('status', 'posted')
                ->where('journal_prefix_id', '!=', 11);
            })
            ->get();

        // Calcular saldo final del libro de bancos (saldo siguiente según libros)
        $saldo_siguiente_libros = $saldo_inicial;
        foreach ($details_query as $detail) {
            $saldo_siguiente_libros += $detail->debit - $detail->credit;
        }

        // Otros campos
        $notas_creditos = 0;
        $cheques_girados = 0;
        $consignaciones = 0;
        $notas_debitos = 0;
        $cuatro_x_mil = 0;
        $total_ingresos = $saldo_inicial + $traslado_a_favor + $notas_creditos + $consignaciones;
        $total_egresos = $cheques_girados + $notas_debitos + $traslados_en_contra + $cuatro_x_mil;
        $saldo_siguiente_extracto = 0;
        $diferencia = 0;

        $company = Company::active();
        $filters = [
            'month' => $month,
            'bank_account' => $bank_account,
            'auxiliar' => $auxiliar,
        ];

        $report_data = compact(
            'company',
            'filters',
            'saldo_inicial',
            'traslado_a_favor',
            'traslados_en_contra',
            'notas_creditos',
            'cheques_girados',
            'notas_debitos',
            'cuatro_x_mil',
            'total_ingresos',
            'total_egresos',
            'saldo_siguiente_libros',
            'saldo_siguiente_extracto',
            'diferencia'
        );

        $pdf = PDF::loadView('accounting::reports.bank_reconciliation_pdf', $report_data)->setPaper('a4', 'landscape');
        $filename = 'Conciliacion_Bancaria_' . date('YmdHis');
        return $pdf->stream($filename . '.pdf');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'bank_account_id' => 'required|integer',
            'month' => 'required|string',
            'date' => 'required|date',
            'saldo_extracto' => 'required',
            'saldo_libro' => 'required',      // <-- agrega esto
            'diferencia' => 'required', 
            'detalles_mas' => 'array',
            'detalles_menos' => 'array',
        ]);
        // 1. Validar que no exista otra conciliación para el mismo banco y mes
        $exists = BankReconciliation::where('bank_account_id', $data['bank_account_id'])
            ->where('month', $data['month'])
            ->exists();
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe una conciliación para este banco y mes.'
            ], 422);
        }

        // Normaliza el saldo_extracto
        $saldo_extracto = $this->parseNumber($data['saldo_extracto']);
        $saldo_libro = $this->parseNumber($data['saldo_libro']);
        $diferencia = $this->parseNumber($data['diferencia']);

        // 1. Crear la conciliación bancaria
        $reconciliation = BankReconciliation::create([
            'bank_account_id' => $data['bank_account_id'],
            'month' => $data['month'],
            'date' => $data['date'],
            'saldo_extracto' => $saldo_extracto,
            'saldo_libro' => $saldo_libro,        // <-- agrega esto
            'diferencia' => $diferencia, 
            'status' => 'finished',
        ]);

        // 2. Guardar detalles "Más" (entradas)
        foreach ($data['detalles_mas'] ?? [] as $detalle) {
            BankReconciliationDetail::create([
                'bank_reconciliation_id' => $reconciliation->id,
                'journal_entry_detail_id' => $detalle['journal_entry_detail_id'] ?? null, // solo si existe
                'type' => 'entrance',
                'date' => $detalle['date'] ?? null,
                'third_party_name' => $detalle['nombre_tercero'] ?? null,
                'source' => $detalle['origen'] ?? null,
                'support_number' => $detalle['n_soporte'] ?? null,
                'check' => $detalle['cheque'] ?? null,
                'concept' => $detalle['concepto'] ?? null,
                'value' => isset($detalle['credit']) ? $this->parseNumber($detalle['credit']) : 0,
            ]);
        }

        // 3. Guardar detalles "Menos" (salidas)
        foreach ($data['detalles_menos'] ?? [] as $detalle) {
            BankReconciliationDetail::create([
                'bank_reconciliation_id' => $reconciliation->id,
                'journal_entry_detail_id' => $detalle['journal_entry_detail_id'] ?? null, // solo si existe
                'type' => 'exit',
                'date' => $detalle['date'] ?? null,
                'third_party_name' => $detalle['nombre_tercero'] ?? null,
                'source' => $detalle['origen'] ?? null,
                'support_number' => $detalle['n_soporte'] ?? null,
                'check' => $detalle['cheque'] ?? null,
                'concept' => $detalle['concepto'] ?? null,
                'value' => isset($detalle['debit']) ? $this->parseNumber($detalle['debit']) : 0,
            ]);
        }

        return response()->json(['success' => true, 'id' => $reconciliation->id]);
    }
    public function pdf($id)
    {
        $reconciliation = BankReconciliation::with(['bankAccount', 'details'])->findOrFail($id);

        // Aquí puedes preparar los datos que necesites para el PDF
        $company = Company::active();
        $detalles_mas = $reconciliation->details->where('type', 'entrance')->values();
        $detalles_menos = $reconciliation->details->where('type', 'exit')->values();

        $data = [
            'company' => $company,
            'reconciliation' => $reconciliation,
            'detalles_mas' => $detalles_mas,
            'detalles_menos' => $detalles_menos,
        ];

        $mpdf = new Mpdf();
        $html = view('accounting::pdf.bank_reconciliation_pdf', $data)->render();
        $mpdf->WriteHTML($html);
        $filename = 'Conciliacion_Bancaria_' . $reconciliation->id . '.pdf';
        return $mpdf->Output($filename, 'I');
    }
    private function parseNumber($value) {
        if (is_numeric($value)) return floatval($value);
        if (strpos($value, ',') !== false) {
            // Formato europeo: 11.546,31
            return floatval(str_replace(',', '.', str_replace('.', '', $value)));
        }
        // Formato estándar: 11546.31
        return floatval($value);
    }

    public function exportExcel(Request $request)
    {
        $month = $request->input('month');
        $bank_account_id = $request->input('bank_account_id');

        // Busca la conciliación bancaria
        $reconciliation = BankReconciliation::with(['bankAccount', 'details'])->where('month', $month)
            ->where('bank_account_id', $bank_account_id)
            ->first();

        if (!$reconciliation) {
            return response()->json(['error' => 'No se encontró la conciliación.'], 404);
        }

        $company = Company::active();
        $bankAccount = $reconciliation->bankAccount;
        $detalles_mas = $reconciliation->details->where('type', 'entrance')->values();
        $detalles_menos = $reconciliation->details->where('type', 'exit')->values();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Datos de la empresa
        $sheet->setCellValue('A1', $company->name);
        $sheet->setCellValue('A2', 'NIT:');
        $sheet->setCellValue('B2', $company->identification_number . ($company->dv ? '-' . $company->dv : ''));
        $sheet->setCellValue('A3', 'Dirección:');
        $sheet->setCellValue('B3', $company->address);
        $sheet->setCellValue('A4', 'Teléfono:');
        $sheet->setCellValue('B4', $company->phone ?? $company->telephone ?? '');

        // Datos de la conciliación
        $sheet->setCellValue('A6', 'Conciliación Bancaria');
        $sheet->setCellValue('A7', 'Banco:');
        $sheet->setCellValue('B7', $bankAccount->bank->description ?? '');
        $sheet->setCellValue('A8', 'N° Cuenta:');
        $sheet->setCellValue('B8', $bankAccount->number ?? '');
        $sheet->setCellValue('A9', 'Tipo de cuenta:');
        $sheet->setCellValue('B9', $bankAccount->description ?? '');
        $sheet->setCellValue('A10', 'Moneda:');
        $sheet->setCellValue('B10', $bankAccount->currency->name ?? '');
        $sheet->setCellValue('A11', 'Periodo (Mes):');
        $sheet->setCellValue('B11', $reconciliation->month);

        $sheet->setCellValue('A13', 'Saldo en extracto:');
        $sheet->setCellValue('B13', $reconciliation->saldo_extracto);
        $sheet->setCellValue('A14', 'Saldo en libros:');
        $sheet->setCellValue('B14', $reconciliation->saldo_libro);
        $sheet->setCellValue('A15', 'Diferencia a conciliar:');
        $sheet->setCellValue('B15', $reconciliation->diferencia);

        // Entradas (Más)
        $row = 17;
        $sheet->setCellValue('A' . $row, 'Entradas (Créditos)');
        $row++;
        $sheet->fromArray([
            'Fecha', 'Tercero', 'Cheque', 'Origen', 'N° Soporte', 'Cheque', 'Concepto', 'Valor'
        ], null, 'A' . $row);
        $row++;
        $total_mas = 0;
        foreach ($detalles_mas as $detalle) {
            $sheet->fromArray([
                $detalle->date,
                $detalle->third_party_name,
                $detalle->check,
                $detalle->source,
                $detalle->support_number,
                $detalle->check,
                $detalle->concept,
                $detalle->value,
            ], null, 'A' . $row);
            $total_mas += $detalle->value;
            $row++;
        }
        $sheet->setCellValue('G' . $row, 'Total Entradas');
        $sheet->setCellValue('H' . $row, $total_mas);
        $row += 2;

        // Salidas (Menos)
        $sheet->setCellValue('A' . $row, 'Salidas (Débitos)');
        $row++;
        $sheet->fromArray([
            'Fecha', 'Tercero', 'Cheque', 'Origen', 'N° Soporte', 'Cheque', 'Concepto', 'Valor'
        ], null, 'A' . $row);
        $row++;
        $total_menos = 0;
        foreach ($detalles_menos as $detalle) {
            $sheet->fromArray([
                $detalle->date,
                $detalle->third_party_name,
                $detalle->check,
                $detalle->source,
                $detalle->support_number,
                $detalle->check,
                $detalle->concept,
                $detalle->value,
            ], null, 'A' . $row);
            $total_menos += $detalle->value;
            $row++;
        }
        $sheet->setCellValue('G' . $row, 'Total Salidas');
        $sheet->setCellValue('H' . $row, $total_menos);
        $row += 2;

        $diferencia_bd = round($reconciliation->diferencia, 2);
        $diferencia_tablas = round($total_mas - $total_menos, 2);
        $diferencia_final = $diferencia_bd + $diferencia_tablas;

        // Totales finales
        $sheet->setCellValue('G' . $row, 'Diferencia Conciliada');
        $sheet->setCellValue('H' . $row, $diferencia_tablas);
        $row++;

        $sheet->setCellValue('G' . $row, 'Diferencia a conciliar');
        $sheet->setCellValue('H' . $row, $diferencia_bd);
        $row++;

        $sheet->setCellValue('G' . $row, 'Diferencia a conciliar + Diferencia conciliada');
        $sheet->setCellValue('H' . $row, $diferencia_final);

        if ($diferencia_final != 0) {
            $sheet->getStyle('H' . $row)->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);
        } else {
            $sheet->getStyle('H' . $row)->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_GREEN);
        }        

        // Descargar el archivo Excel
        $filename = 'conciliacion_bancaria_' . $reconciliation->month . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), $filename);
        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }
}