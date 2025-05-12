<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\Accounting\Models\ChartOfAccount;

class CreateAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code', 10)->unique(); // Código contable (Ej: 11050501)
            $table->string('name'); // Nombre de la cuenta (Ej: Caja General)
            $table->enum('type', ['Asset', 'Liability', 'Equity', 'Revenue', 'Expense', 'Cost']); // Tipo de cuenta
            $table->unsignedBigInteger('parent_id')->nullable(); // Relación padre-hijo
            $table->integer('level'); // Nivel jerárquico (1 a 4)
            $table->boolean('status')->default(1); // Activo/Inactivo
            $table->timestamps();

            // Clave foránea para la relación padre-hijo
            $table->foreign('parent_id')->references('id')->on('chart_of_accounts');
        });

        $this->importAccountsFromCSV();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
        });
        Schema::dropIfExists('chart_of_accounts');
    }
    
    private function importAccountsFromCSV()
    {
        $file = public_path('csv/cuentas_contables.csv');
        if (!file_exists($file)) {
            throw new Exception("El archivo cuentas.csv no fue encontrado en la carpeta public.");
        }

        $handle = fopen($file, 'r');
        $header = fgetcsv($handle, 1000, ','); // Leer cabecera

        // Eliminar cualquier BOM (Byte Order Mark) de la cabecera
        $header = array_map('trim', $header);

        // Verificar que la cabecera esté correctamente leída
        if (!$header || count($header) < 5) {
            throw new Exception("El archivo CSV no tiene el formato esperado.");
        }

        $accounts = [];
        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            // Eliminar cualquier BOM de los datos de la fila
            $row = array_map('trim', $row);

            // Verificar que la fila tenga el mismo número de columnas que la cabecera
            if (count($row) !== count($header)) {
                continue;
            }

            $data = [
                'code' => $row[0],
                'name' => $row[1],
                'type' => $row[2],
                'level' => $row[3],
                'parent_code'=> $row[4]
            ];

            // Verificar que la clave 'code' esté presente y no vacía
            if (!isset($data['code']) || empty($data['code'])) {
                continue;
            }

            // Asignar la cuenta por su código
            $accounts[$data['code']] = $data;
        }

        fclose($handle);

        // dd($accounts);
        // Insertar cuentas respetando jerarquía
        $inserted = [];

        foreach ($accounts as $code => $data) {
            // Verificar si 'parent_code' está vacío, y asignar parentId si es necesario
            $parentId = null;

            // Si 'parent_code' está vacío, no se asigna un 'parent_id'
            if (!empty($data['parent_code']) && isset($inserted[$data['parent_code']])) {
                $parentId = $inserted[$data['parent_code']];
            }

            // Crear la cuenta
            $account = ChartOfAccount::create([
                'code' => $data['code'],
                'name' => $data['name'],
                'type' => $data['type'],
                'level' => $data['level'],
                'parent_id' => $parentId,  // Asignar 'parent_id' cuando sea aplicable
                'status' => true,
            ]);

            // Registrar la cuenta insertada
            $inserted[$code] = $account->id;
        }
    }

}
