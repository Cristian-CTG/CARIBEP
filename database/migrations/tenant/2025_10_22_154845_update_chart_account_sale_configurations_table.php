<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdateChartAccountSaleConfigurationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Si no existe la columna inventory_account, la crea y actualiza los registros existentes
        if (!Schema::hasColumn('chart_account_sale_configurations', 'inventory_account')) {
            Schema::table('chart_account_sale_configurations', function (Blueprint $table) {
                $table->string('inventory_account', 10)->nullable()->after('sales_returns_account');
            });

            // Actualiza todos los registros existentes con el valor por defecto
            DB::table('chart_account_sale_configurations')
                ->whereNull('inventory_account')
                ->update(['inventory_account' => '143505']);
        }

        // Si no existe la columna sale_cost_account, la crea y actualiza los registros existentes
        if (!Schema::hasColumn('chart_account_sale_configurations', 'sale_cost_account')) {
            Schema::table('chart_account_sale_configurations', function (Blueprint $table) {
                $table->string('sale_cost_account', 10)->nullable()->after('inventory_account');
            });

            // Actualiza todos los registros existentes con el valor por defecto
            DB::table('chart_account_sale_configurations')
                ->whereNull('sale_cost_account')
                ->update(['sale_cost_account' => '613595']);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // if (Schema::hasColumn('chart_account_sale_configurations', 'inventory_account')) {
        //     Schema::table('chart_account_sale_configurations', function (Blueprint $table) {
        //         $table->dropColumn('inventory_account');
        //     });
        // }
        // if (Schema::hasColumn('chart_account_sale_configurations', 'sale_cost_account')) {
        //     Schema::table('chart_account_sale_configurations', function (Blueprint $table) {
        //         $table->dropColumn('sale_cost_account');
        //     });
        // }
    }
}
