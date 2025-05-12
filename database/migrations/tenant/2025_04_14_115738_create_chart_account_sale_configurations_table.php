<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateChartAccountSaleConfigurationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chart_account_sale_configurations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('income_account', 10)->nullable();
            $table->string('sales_returns_account', 10)->nullable();
            $table->string('accounting_clasification')->nullable();
            $table->timestamps();
        });

        DB::table('chart_account_sale_configurations')->insert([
            ['income_account' => '143505', 'sales_returns_account' => '143505', 'accounting_clasification' => 'Comercialización de productos']
        ]);

        

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chart_account_sale_configurations');
    }
}
