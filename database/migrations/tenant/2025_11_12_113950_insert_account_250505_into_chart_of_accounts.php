<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InsertAccount250505IntoChartOfAccounts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $exists = \DB::table('chart_of_accounts')->where('code', '250505')->exists();
        if (!$exists) {
            \DB::table('chart_of_accounts')->insert([
                'code' => '250505',
                'name' => 'Sueldos por pagar',
                'type' => 'Liability',
                'parent_id' => 370, // Ajusta si debe tener padre
                'level' => 4,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
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
        //
    }
}
