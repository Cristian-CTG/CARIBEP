<?php

namespace Modules\Accounting\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalPrefix;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Accounting\Exports\JournalEntriesExport;
use Modules\Accounting\Imports\JournalEntriesImport;

class JournalEntryExportImportController extends Controller
{
    public function exportExcel(Request $request)
    {
        $prefix_id = $request->input('prefix_id');
        $date_from = $request->input('date_from');
        $date_to = $request->input('date_to');

        $query = JournalEntry::with([
            'details.chartOfAccount',
            'details.thirdParty',
            'details.bankAccount',
            'journal_prefix'
        ])->orderBy('date')->orderBy('number');

        if ($prefix_id) {
            $query->where('journal_prefix_id', $prefix_id);
        } else {
            // Filtra solo los prefijos modificables
            $modifiablePrefixIds = JournalPrefix::where('modifiable', true)->pluck('id')->toArray();
            $query->whereIn('journal_prefix_id', $modifiablePrefixIds);
        }
        if ($date_from && $date_to) {
            $query->whereBetween('date', [$date_from, $date_to]);
        }

        $entries = $query->get();

        // Exportar usando un Export personalizado (debes crearlo)
        return Excel::download(new JournalEntriesExport($entries), 'asientos_contables.xlsx');
    }

    /**
     * Importa asientos contables desde Excel (xls/xlsx) usando la plantilla unificada.
     * Reglas:
     *  - Se agrupa por (prefix, number)
     *  - Debe balancear por asiento (sum(debit) == sum(credit))
     *  - Valida existencia de prefijo y cuentas contables
     *  - Crea/actualiza tercero si viene documento
     *  - Asocia banco por BankAccount.description si viene bank_name
     */
    public function importExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xls,xlsx|max:20480', // 20MB
        ]);

        try {
            $import = new JournalEntriesImport(auth()->id());
            Excel::import($import, $request->file('file'));

            return response()->json([
                'success' => true,
                'message' => 'Importación completada.',
                'summary' => [
                    'created_entries' => $import->getCreatedEntries(),
                    'skipped_rows'    => $import->getSkippedRows(),
                ],
                'errors'  => $import->getRowErrors(), // detalle por fila (si hubo)
            ]);
        } catch (\Throwable $e) {
            \Log::error('Import journal entries failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error procesando el archivo: '.$e->getMessage(),
            ], 422);
        }
    }
}