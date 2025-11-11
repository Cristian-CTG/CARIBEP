<?php

namespace Modules\Accounting\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class TrialBalanceExport implements FromView, ShouldAutoSize, WithStyles, WithEvents
{
    protected $data;
    protected $totals;
    protected $company;
    protected $dateStart;
    protected $dateEnd;

    public function __construct($data, $totals, $company, $dateStart, $dateEnd)
    {
        $this->data = $data;
        $this->totals = $totals;
        $this->company = $company;
        $this->dateStart = $dateStart;
        $this->dateEnd = $dateEnd;
    }

    public function view(): View
    {
        return view('accounting::reports.trial_balance_excel', [
            'data' => $this->data,
            'totals' => $this->totals,
            'company' => $this->company,
            'dateStart' => $this->dateStart,
            'dateEnd' => $this->dateEnd,
        ]);
    }

    /**
     * Aplica estilos después de generar la vista.
     */
    public function styles(Worksheet $sheet)
    {

        $sheet->getStyle('A1:F5')->getFont()->setBold(true);

        $sheet->getStyle('A1:F4')->getAlignment()->setHorizontal('center');

        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle("A5:F{$lastRow}")
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $sheet->getStyle("C6:F{$lastRow}")
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');
        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                foreach (range('A', 'F') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                $sheet->getColumnDimension('B')->setWidth(40);
            }
        ];
    }
}
