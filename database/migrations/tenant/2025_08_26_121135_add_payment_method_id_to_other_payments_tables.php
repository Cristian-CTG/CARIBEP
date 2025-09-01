<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddPaymentMethodIdToOtherPaymentsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // quotation_payments
        DB::statement('ALTER TABLE quotation_payments MODIFY payment_method_type_id CHAR(2) NULL;');
        Schema::table('quotation_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('quotation_payments', 'payment_method_id')) {
                $table->unsignedBigInteger('payment_method_id')->nullable()->after('payment_method_type_id');
                $table->foreign('payment_method_id')
                    ->references('id')
                    ->on('co_payment_methods')
                    ->onDelete('set null');
            }
        });

        // document_pos_payments
        DB::statement('ALTER TABLE documents_pos_payments MODIFY payment_method_type_id CHAR(2) NULL;');
        Schema::table('documents_pos_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('documents_pos_payments', 'payment_method_id')) {
                $table->unsignedBigInteger('payment_method_id')->nullable()->after('payment_method_type_id');
                $table->foreign('payment_method_id')
                    ->references('id')
                    ->on('co_payment_methods')
                    ->onDelete('set null');
            }
        });

        // co_remission_payments
        DB::statement('ALTER TABLE co_remission_payments MODIFY payment_method_type_id CHAR(2) NULL;');
        Schema::table('co_remission_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('co_remission_payments', 'payment_method_id')) {
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
        // quotation_payments
        Schema::table('quotation_payments', function (Blueprint $table) {
            if (Schema::hasColumn('quotation_payments', 'payment_method_id')) {
                $table->dropForeign(['payment_method_id']);
                $table->dropColumn('payment_method_id');
            }
        });
        DB::statement('ALTER TABLE quotation_payments MODIFY payment_method_type_id CHAR(2) NOT NULL;');

        // document_pos_payments
       Schema::table('documents_pos_payments', function (Blueprint $table) {
            if (Schema::hasColumn('documents_pos_payments', 'payment_method_id')) {
                $table->dropForeign(['payment_method_id']);
                $table->dropColumn('payment_method_id');
            }
        });
        DB::statement('ALTER TABLE documents_pos_payments MODIFY payment_method_type_id CHAR(2) NOT NULL;');

        // co_remission_payments
        Schema::table('co_remission_payments', function (Blueprint $table) {
            if (Schema::hasColumn('co_remission_payments', 'payment_method_id')) {
                $table->dropForeign(['payment_method_id']);
                $table->dropColumn('payment_method_id');
            }
        });
        DB::statement('ALTER TABLE co_remission_payments MODIFY payment_method_type_id CHAR(2) NOT NULL;');
    }
}
