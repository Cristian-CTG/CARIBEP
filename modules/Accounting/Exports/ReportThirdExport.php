<?php

namespace Modules\Accounting\Exports;

use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Contracts\View\View;

class ReportThirdExport implements FromView
{
    protected $viewName;
    protected $data;

    public function __construct($viewName, $data)
    {
        $this->viewName = $viewName;
        $this->data = $data;
    }

    public function view(): View
    {
        return view($this->viewName, $this->data);
    }
}