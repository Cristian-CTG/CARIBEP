<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateInventoryAccountInAccountingChartAccountConfigurationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('accounting_chart_account_configurations')
            ->where(function ($query) {
                $query->whereNull('inventory_account')
                    ->orWhere('inventory_account', '');
            })
            ->update(['inventory_account' => '143505']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No revert action defined
    }
}
