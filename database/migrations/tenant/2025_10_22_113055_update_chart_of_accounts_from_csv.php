<?php
use Illuminate\Database\Migrations\Migration;
use Modules\Accounting\Models\ChartOfAccount;

class UpdateChartOfAccountsFromCsv extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->syncAccountsFromCSV(public_path('csv/cuentas_contables.csv'));
        $this->syncAccountsFromCSV(public_path('csv/cuentas_contables_update.csv'));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No se recomienda eliminar cuentas contables en reversión
    }

    private function syncAccountsFromCSV($file)
    {
        if (!file_exists($file)) {
            throw new Exception("El archivo $file no fue encontrado.");
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
}