<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateAccountingChartAccountConfigurationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('accounting_chart_account_configurations')
            ->whereNull('inventory_account')
            ->update(['inventory_account' => '143505']);

        DB::table('accounting_chart_account_configurations')
            ->whereNull('inventory_adjustment_account')
            ->update(['inventory_adjustment_account' => '618005']);

        DB::table('accounting_chart_account_configurations')
            ->update(['sale_cost_account' => '613595']);

        DB::table('accounting_chart_account_configurations')
            ->update(['customer_receivable_account' => '130505']);

        DB::table('accounting_chart_account_configurations')
            ->whereNull('customer_returns_account')
            ->update(['customer_returns_account' => '130505']);

        DB::table('accounting_chart_account_configurations')
            ->update(['supplier_payable_account' => '220505']);

        DB::table('accounting_chart_account_configurations')
            ->whereNull('supplier_returns_account')
            ->update(['supplier_returns_account' => '220505']);

        DB::table('accounting_chart_account_configurations')
            ->whereNull('retained_earning_account')
            ->update(['retained_earning_account' => '370505']);

        DB::table('accounting_chart_account_configurations')
            ->whereNull('profit_period_account')
            ->update(['profit_period_account' => '360505']);

        DB::table('accounting_chart_account_configurations')
            ->whereNull('lost_period_account')
            ->update(['lost_period_account' => '361005']);

        DB::table('accounting_chart_account_configurations')
            ->whereNull('adjustment_opening_balance_banks_account')
            ->update(['adjustment_opening_balance_banks_account' => null]);

        DB::table('accounting_chart_account_configurations')
            ->whereNull('adjustment_opening_balance_banks_inventory')
            ->update(['adjustment_opening_balance_banks_inventory' => null]);
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
