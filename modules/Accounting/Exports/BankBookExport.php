<?php

namespace Modules\Accounting\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class BankBookExport implements FromView, ShouldAutoSize
{
    use Exportable;

    protected $report_data;

    public function __construct($report_data)
    {
        $this->report_data = $report_data;
    }

    public function view(): View
    {
        return view('accounting::reports.bank_book_excel', [
            'details'      => $this->report_data['details'],
            'company'      => $this->report_data['company'],
            'filters'      => $this->report_data['filters'],
            'saldo_inicial'=> $this->report_data['saldo_inicial'],
            'saldo_final'  => $this->report_data['saldo_final'],
        ]);
    }
}