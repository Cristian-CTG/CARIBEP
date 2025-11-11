<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\Accounting\Models\ChartOfAccount;

class ImportAccounts789FromCsv extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $file = public_path('csv/accounts_7_8_9.csv');
        if (!file_exists($file)) {
            throw new Exception("El archivo accounts_7_8_9.csv no fue encontrado en la carpeta public/csv.");
        }

        $handle = fopen($file, 'r');
        $header = fgetcsv($handle, 1000, ',');
        $header = array_map('trim', $header);

        if (!$header || count($header) < 5) {
            throw new Exception("El archivo CSV no tiene el formato esperado.");
        }

        $accounts = [];
        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            $row = array_map('trim', $row);
            if (count($row) !== count($header)) continue;
            $data = [
                'code' => $row[0],
                'name' => $row[1],
                'type' => $row[2],
                'level' => $row[3],
                'parent_code'=> $row[4]
            ];
            if (!isset($data['code']) || empty($data['code'])) continue;
            $accounts[$data['code']] = $data;
        }
        fclose($handle);

        $inserted = [];
        foreach ($accounts as $code => $data) {
            $parentId = null;
            if (!empty($data['parent_code'])) {
                $parentId = ChartOfAccount::where('code', $data['parent_code'])->value('id');
            }
            $account = ChartOfAccount::where('code', $code)->first();
            if ($account) {
                $account->update([
                    'name' => $data['name'],
                    'type' => $data['type'],
                    'level' => $data['level'],
                    'parent_id' => $parentId,
                    'status' => true,
                ]);
            } else {
                ChartOfAccount::create([
                    'code' => $data['code'],
                    'name' => $data['name'],
                    'type' => $data['type'],
                    'level' => $data['level'],
                    'parent_id' => $parentId,
                    'status' => true,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        
    }
}
