<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TenantAddChartAccountSaleToCoTaxes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('co_taxes', function (Blueprint $table) {
            $table->string('chart_account_sale', 10)->nullable();
            $table->string('chart_account_purchase', 10)->nullable();
            $table->string('chart_account_return_sale', 10)->nullable();
            $table->string('chart_account_return_purchase', 10)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('co_taxes', function (Blueprint $table) {
            $table->dropColumn('chart_account_sale');
            $table->dropColumn('chart_account_purchase');
            $table->dropColumn('chart_account_return_sale');
            $table->dropColumn('chart_account_return_purchase');
        });
    }
}
