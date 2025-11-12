<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPayrollAccountToAccountingChartAccountConfigurationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('accounting_chart_account_configurations', function (Blueprint $table) {
            Schema::table('accounting_chart_account_configurations', function (Blueprint $table) {
                $table->string('payroll_account', 10)->nullable()->after('adjustment_opening_balance_banks_inventory');
            });

            // Solo actualiza si ya existe una fila
            $row = \DB::table('accounting_chart_account_configurations')->first();
            if ($row) {
                \DB::table('accounting_chart_account_configurations')->update([
                    'payroll_account' => '250505',
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('accounting_chart_account_configurations', function (Blueprint $table) {
            $table->dropColumn('payroll_account');
        });
    }
}
