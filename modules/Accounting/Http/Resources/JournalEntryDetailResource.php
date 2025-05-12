<?php

namespace Modules\Accounting\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class JournalEntryDetailResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'date' => optional($this->journalEntry)->date, // Si `journalEntry` tiene una fecha
            'chart_account_code' => optional($this->chartOfAccount)->code, // Asumiendo que en el modelo ChartOfAccount existe una columna `code`
            'chart_account_name' => optional($this->chartOfAccount)->name, // Asumiendo que en el modelo ChartOfAccount existe una columna `name`
            'debit' => $this->debit,
            'credit' => $this->credit,
        ];
    }
}