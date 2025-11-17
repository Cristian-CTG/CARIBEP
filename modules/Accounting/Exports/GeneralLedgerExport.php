<?php

namespace Modules\Accounting\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;

class GeneralLedgerExport  implements FromView, ShouldAutoSize, WithStyles, WithEvents
{
    protected $records;
    protected $company;
    protected $dateStart;
    protected $dateEnd;

    protected $totalRows;

    public function __construct($records, $company, $dateStart, $dateEnd)
    {
        $this->records   = $records;
        $this->company   = $company;
        $this->dateStart = $dateStart;
        $this->dateEnd   = $dateEnd;
        $this->totalRows = $this->calculateRows($records);
    }

    public function view(): View
    {
        return view('accounting::reports.general_ledger_excel', [
            'records'   => $this->records,
            'company'   => $this->company,
            'dateStart' => $this->dateStart,
            'dateEnd'   => $this->dateEnd,
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                // Detectar automáticamente la última fila con contenido
                $lastRow = $sheet->getHighestRow();

                // Aplicar bordes solo al rango con datos
                $sheet->getStyle("A1:F{$lastRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                // AutoSize columnas
                foreach (range('A','F') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            }
        ];
    }
    private function calculateRows($records)
    {
        $rows = 4; // Encabezados (ajusta según tu template)

        foreach ($records as $acc) {
            $rows++; // título de cuenta
            $rows++; // fila saldo inicial
            $rows += count($acc->movements); // movimientos
        }

        return $rows;
    }
}
