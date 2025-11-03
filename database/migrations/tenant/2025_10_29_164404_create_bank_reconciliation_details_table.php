<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBankReconciliationDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bank_reconciliation_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('bank_reconciliation_id');
            $table->unsignedBigInteger('journal_entry_detail_id')->nullable(); // null para registros manuales
            $table->enum('type', ['entrance', 'exit']); // ingreso o egreso
            $table->date('date')->nullable();
            $table->string('third_party_name')->nullable();
            $table->string('source')->nullable();
            $table->string('support_number')->nullable();
            $table->string('check')->nullable();
            $table->string('concept')->nullable();
            $table->decimal('value', 15, 2);
            $table->timestamps();

            $table->foreign('bank_reconciliation_id')->references('id')->on('bank_reconciliations')->onDelete('cascade');
            // journal_entry_detail_id puede ser null (manual), si no es null, referencia
            $table->foreign('journal_entry_detail_id')->references('id')->on('journal_entry_details')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bank_reconciliation_details');
    }
}
