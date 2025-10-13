<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModifyCashBalanceColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cash', function (Blueprint $table) {
            $table->decimal('final_balance', 15, 2)->change();
            $table->decimal('income', 15, 2)->change();
            $table->decimal('beginning_balance', 15, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cash', function (Blueprint $table) {
            $table->decimal('final_balance', 8, 2)->change();
            $table->decimal('income', 8, 2)->change();
            $table->decimal('beginning_balance', 8, 2)->change();
        });
    }
}
