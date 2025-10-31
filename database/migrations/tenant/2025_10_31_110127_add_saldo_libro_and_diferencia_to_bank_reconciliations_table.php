<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSaldoLibroAndDiferenciaToBankReconciliationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bank_reconciliations', function (Blueprint $table) {
            $table->decimal('saldo_libro', 15, 2)->nullable()->after('saldo_extracto');
            $table->decimal('diferencia', 15, 2)->nullable()->after('saldo_libro');
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
            $table->dropColumn(['saldo_libro', 'diferencia']);
        });
    }
}
