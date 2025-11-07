<?php

namespace Modules\Accounting\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class JournalEntriesImportFormatExport implements FromView
{
    public function view(): View
    {
        return view('accounting::exports.journal_entries_import_format');
    }
}