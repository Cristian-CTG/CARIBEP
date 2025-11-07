<?php

namespace Modules\Accounting\Imports;

use Modules\Accounting\Models\ChartOfAccount;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ChartOfAccountImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        $typeMap = [
            '1' => 'Asset',
            '2' => 'Liability',
            '3' => 'Equity',
            '4' => 'Revenue',
            '5' => 'Expense',
            '6' => 'Cost',
            '7' => 'ProductionCost',
            '8' => 'OrderDebit',
            '9' => 'OrderCredit',
        ];

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        DB::transaction(function () use ($rows, $typeMap, &$created, &$updated, &$skipped, &$errors) {
            foreach ($rows as $index => $row) {
                // Mapear cabeceras en español a las esperadas
                if (isset($row['codigo'])) $row['code'] = $row['codigo'];
                if (isset($row['nombre'])) $row['name'] = $row['nombre'];

                if (empty($row['code']) || empty($row['name'])) {
                    $skipped++;
                    $errors[] = "Fila " . ($index + 1) . ": falta código o nombre.";
                    continue;
                }

                $code = trim($row['code']);
                $name = trim($row['name']);

                if (!preg_match('/^[0-9.]+$/', $code)) {
                    $skipped++;
                    $errors[] = "Fila " . ($index + 1) . ": código inválido ($code).";
                    continue;
                }

                if (ChartOfAccount::where('code', $code)->exists()) {
                    $skipped++;
                    $errors[] = "Fila " . ($index + 1) . ": la cuenta con código $code ya existe y fue omitida.";
                    continue;
                }

                $level = $this->getLevelByCode($code);
                $type = $typeMap[substr($code, 0, 1)] ?? 'Asset';

                // Si no hay parent_code, lo infiere
                $parentCode = $row['parent_code'] ?? $this->inferParentCode($code);
                $parentId = null;

                if (!empty($parentCode)) {
                    $parent = ChartOfAccount::where('code', $parentCode)->first();
                    if ($parent) $parentId = $parent->id;
                }

                ChartOfAccount::create([
                    'code' => $code,
                    'name' => $name,
                    'type' => $type,
                    'level' => $level,
                    'parent_id' => $parentId,
                    'status' => 1,
                ]);

                $created++;
            }
        });

        \Log::info("Importación completada: {$created} creados, {$skipped} omitidos.", $errors);
    }

    private function getLevelByCode($code)
    {
        $length = strlen(preg_replace('/\D/', '', $code));
        if ($length <= 1) return 1;
        if ($length <= 2) return 2;
        if ($length <= 4) return 3;
        if ($length <= 6) return 4;
        if ($length <= 8) return 5;
        return 6;
    }

    private function inferParentCode($code)
    {
        $clean = preg_replace('/\D/', '', $code);
        $len = strlen($clean);

        if ($len <= 1) return null;
        if ($len <= 2) return substr($clean, 0, 1);
        if ($len <= 4) return substr($clean, 0, 2);
        if ($len <= 6) return substr($clean, 0, 4);
        if ($len <= 8) return substr($clean, 0, 6);
        return substr($clean, 0, 8);
    }
}
