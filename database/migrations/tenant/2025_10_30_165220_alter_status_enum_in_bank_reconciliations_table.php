<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AlterStatusEnumInBankReconciliationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE bank_reconciliations MODIFY COLUMN status ENUM('draft', 'pending', 'finished') DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE bank_reconciliations MODIFY COLUMN status ENUM('pending', 'finished') DEFAULT 'pending'");
    }
}
