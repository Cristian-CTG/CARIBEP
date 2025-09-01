<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddPaymentMethodIdToDocumentPayments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE document_payments MODIFY payment_method_type_id CHAR(2) NULL;');

        Schema::table('document_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('document_payments', 'payment_method_id')) {
                $table->unsignedBigInteger('payment_method_id')->nullable()->after('payment_method_type_id');
                $table->foreign('payment_method_id')
                    ->references('id')
                    ->on('co_payment_methods')
                    ->onDelete('set null');
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
        Schema::table('document_payments', function (Blueprint $table) {
            $table->dropForeign(['payment_method_id']);
            $table->dropColumn('payment_method_id');
        });
        DB::statement('ALTER TABLE document_payments MODIFY payment_method_type_id CHAR(2) NOT NULL;');
    }
}
