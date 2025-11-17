<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdatePercentagesInCoTypeOvertimeSurcharges extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Actualizar los porcentajes de los tipos de horas extra
        $updates = [
            ['code' => 4, 'percentage' => 105.00], // Hora Extra Diurna Dominical y Festivos
            ['code' => 5, 'percentage' => 80.00],  // Hora Recargo Diurno Dominical y Festivos
            ['code' => 6, 'percentage' => 155.00], // Hora Extra Nocturna Dominical y Festivos
            ['code' => 7, 'percentage' => 115.00], // Hora Recargo Nocturno Dominical y Festivos
        ];

        foreach ($updates as $update) {
            DB::connection('tenant')->table('co_type_overtime_surcharges')
                ->where('code', $update['code'])
                ->update(['percentage' => $update['percentage']]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revertir los porcentajes a los valores anteriores
        $reverts = [
            ['code' => 4, 'percentage' => 100.00], // Hora Extra Diurna Dominical y Festivos
            ['code' => 5, 'percentage' => 75.00],  // Hora Recargo Diurno Dominical y Festivos
            ['code' => 6, 'percentage' => 150.00], // Hora Extra Nocturna Dominical y Festivos
            ['code' => 7, 'percentage' => 110.00], // Hora Recargo Nocturno Dominical y Festivos
        ];

        foreach ($reverts as $revert) {
            DB::connection('tenant')->table('co_type_overtime_surcharges')
                ->where('code', $revert['code'])
                ->update(['percentage' => $revert['percentage']]);
        }
    }
}
