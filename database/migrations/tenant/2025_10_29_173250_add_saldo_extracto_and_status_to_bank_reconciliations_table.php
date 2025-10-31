<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSaldoExtractoAndStatusToBankReconciliationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bank_reconciliations', function (Blueprint $table) {
            $table->decimal('saldo_extracto', 15, 2)->nullable()->after('date');
            $table->enum('status', ['pending', 'finished'])->default('pending')->after('saldo_extracto');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bank_reconciliations', function (Blueprint $table) {
            $table->dropColumn('saldo_extracto');
            $table->dropColumn('status');
        });
    }
}
