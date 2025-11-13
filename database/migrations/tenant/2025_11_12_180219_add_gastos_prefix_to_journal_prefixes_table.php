<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddGastosPrefixToJournalPrefixesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Insertar el prefijo para gastos si no existe
        if (!\DB::table('journal_prefixes')->where('prefix', 'GD')->exists()) {
            \DB::table('journal_prefixes')->insert([
                'prefix' => 'GD',
                'description' => 'Gastos Diversos',
                'modifiable' => false,
            ]);
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
