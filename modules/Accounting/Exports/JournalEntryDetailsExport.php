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

class JournalEntryDetailsExport implements FromView, ShouldAutoSize, WithStyles, WithEvents
{
    protected $entries;
    protected $company;
    protected $filters;
    protected $totalDebit;
    protected $totalCredit;

    public function __construct($entries, $company, $filters, $totalDebit, $totalCredit)
    {
        $this->entries = $entries;
        $this->company = $company;
        $this->filters = $filters;
        $this->totalDebit = $totalDebit;
        $this->totalCredit = $totalCredit;
    }

    public function view(): View
    {
        return view('accounting::reports.journal_entry_details_excel', [
            'entries' => $this->entries,
            'company' => $this->company,
            'filters' => $this->filters,
            'total_debit' => $this->totalDebit,
            'total_credit' => $this->totalCredit,
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
                $lastRow = $sheet->getHighestRow();
                $lastCol = $sheet->getHighestColumn();

                $sheet->getStyle("A1:{$lastCol}{$lastRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                $sheet->getStyle("A1:{$lastCol}4")
                    ->getAlignment()
                    ->setHorizontal('center');

                foreach (range('A', $lastCol) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                    $sheet->getColumnDimension($col)->setWidth(
                        $sheet->getColumnDimension($col)->getAutoSize()
                    );
                }
            }
        ];
    }
}
