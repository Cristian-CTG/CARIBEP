<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class InsertContactsAndProductsModulesIntoModulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('modules')->insert([ 
            ['id' => 22, 'value' => 'contacts', 'description' => 'Contactos'],
            ['id' => 23, 'value' => 'products', 'description' => 'Productos'],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('modules')->whereIn('id', [22, 23])->delete();
    }
}
