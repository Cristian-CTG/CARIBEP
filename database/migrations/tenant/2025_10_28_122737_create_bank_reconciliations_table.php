<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBankReconciliationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bank_reconciliations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('bank_account_id');
            $table->string('month', 7);
            $table->date('date');
            $table->timestamps();

            $table->foreign('bank_account_id')
                ->references('id')->on('bank_accounts')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bank_reconciliations');
    }
}
