<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;


class AlterChartOfAccountsTypeEnum extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            DB::statement("ALTER TABLE chart_of_accounts MODIFY COLUMN type ENUM(
                'Asset',
                'Liability',
                'Equity',
                'Revenue',
                'Expense',
                'Cost',
                'ProductionCost',
                'OrderDebit',
                'OrderCredit'
            ) NOT NULL;");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            DB::statement("ALTER TABLE chart_of_accounts MODIFY COLUMN type ENUM(
                'Asset',
                'Liability',
                'Equity',
                'Revenue',
                'Expense',
                'Cost'
            ) NOT NULL;");
        });
    }
}
