<?php

namespace Modules\Inventory\Exports;

use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;

class FormatMassiveExport implements FromView, ShouldAutoSize
{
    use Exportable;
    public function view(): View
    {
        return view('inventory::inventory.Format_massive');
    }
}