<?php

namespace Modules\Accounting\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class JournalEntriesExport implements FromView
{
    protected $entries;

    public function __construct($entries)
    {
        $this->entries = $entries;
    }

    public function view(): View
    {
        return view('accounting::exports.journal_entries_unified', [
            'entries' => $this->entries
        ]);
    }
}