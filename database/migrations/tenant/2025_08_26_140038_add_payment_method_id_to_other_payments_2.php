<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddPaymentMethodIdToOtherPayments2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // sale_note_payments
        if (Schema::hasTable('sale_note_payments')) {
            DB::statement('ALTER TABLE sale_note_payments MODIFY payment_method_type_id CHAR(2) NULL;');
            Schema::table('sale_note_payments', function (Blueprint $table) {
                if (!Schema::hasColumn('sale_note_payments', 'payment_method_id')) {
                    $table->unsignedBigInteger('payment_method_id')->nullable()->after('payment_method_type_id');
                    $table->foreign('payment_method_id')
                        ->references('id')
                        ->on('co_payment_methods')
                        ->onDelete('set null');
                }
            });
        }

        // purchase_payments
        if (Schema::hasTable('purchase_payments')) {
            DB::statement('ALTER TABLE purchase_payments MODIFY payment_method_type_id CHAR(2) NULL;');
            Schema::table('purchase_payments', function (Blueprint $table) {
                if (!Schema::hasColumn('purchase_payments', 'payment_method_id')) {
                    $table->unsignedBigInteger('payment_method_id')->nullable()->after('payment_method_type_id');
                    $table->foreign('payment_method_id')
                        ->references('id')
                        ->on('co_payment_methods')
                        ->onDelete('set null');
                }
            });
        }

        // // expense_payments
        // if (Schema::hasTable('expense_payments')) {
        //     DB::statement('ALTER TABLE expense_payments MODIFY payment_method_type_id CHAR(2) NULL;');
        //     Schema::table('expense_payments', function (Blueprint $table) {
        //         if (!Schema::hasColumn('expense_payments', 'payment_method_id')) {
        //             $table->unsignedBigInteger('payment_method_id')->nullable()->after('payment_method_type_id');
        //             $table->foreign('payment_method_id')
        //                 ->references('id')
        //                 ->on('co_payment_methods')
        //                 ->onDelete('set null');
        //         }
        //     });
        // }

        // contract_payments
        if (Schema::hasTable('contract_payments')) {
            DB::statement('ALTER TABLE contract_payments MODIFY payment_method_type_id CHAR(2) NULL;');
            Schema::table('contract_payments', function (Blueprint $table) {
                if (!Schema::hasColumn('contract_payments', 'payment_method_id')) {
                    $table->unsignedBigInteger('payment_method_id')->nullable()->after('payment_method_type_id');
                    $table->foreign('payment_method_id')
                        ->references('id')
                        ->on('co_payment_methods')
                        ->onDelete('set null');
                }
            });
        }

        // income_payments
        if (Schema::hasTable('income_payments')) {
            DB::statement('ALTER TABLE income_payments MODIFY payment_method_type_id CHAR(2) NULL;');
            Schema::table('income_payments', function (Blueprint $table) {
                if (!Schema::hasColumn('income_payments', 'payment_method_id')) {
                    $table->unsignedBigInteger('payment_method_id')->nullable()->after('payment_method_type_id');
                    $table->foreign('payment_method_id')
                        ->references('id')
                        ->on('co_payment_methods')
                        ->onDelete('set null');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // sale_note_payments
        if (Schema::hasTable('sale_note_payments')) {
            Schema::table('sale_note_payments', function (Blueprint $table) {
                if (Schema::hasColumn('sale_note_payments', 'payment_method_id')) {
                    $table->dropForeign(['payment_method_id']);
                    $table->dropColumn('payment_method_id');
                }
            });
            DB::statement('ALTER TABLE sale_note_payments MODIFY payment_method_type_id CHAR(2) NOT NULL;');
        }

        // purchase_payments
        if (Schema::hasTable('purchase_payments')) {
            Schema::table('purchase_payments', function (Blueprint $table) {
                if (Schema::hasColumn('purchase_payments', 'payment_method_id')) {
                    $table->dropForeign(['payment_method_id']);
                    $table->dropColumn('payment_method_id');
                }
            });
            DB::statement('ALTER TABLE purchase_payments MODIFY payment_method_type_id CHAR(2) NOT NULL;');
        }

        // // expense_payments
        // if (Schema::hasTable('expense_payments')) {
        //     Schema::table('expense_payments', function (Blueprint $table) {
        //         if (Schema::hasColumn('expense_payments', 'payment_method_id')) {
        //             $table->dropForeign(['payment_method_id']);
        //             $table->dropColumn('payment_method_id');
        //         }
        //     });
        //     DB::statement('ALTER TABLE expense_payments MODIFY payment_method_type_id CHAR(2) NOT NULL;');
        // }

        // contract_payments
        if (Schema::hasTable('contract_payments')) {
            Schema::table('contract_payments', function (Blueprint $table) {
                if (Schema::hasColumn('contract_payments', 'payment_method_id')) {
                    $table->dropForeign(['payment_method_id']);
                    $table->dropColumn('payment_method_id');
                }
            });
            DB::statement('ALTER TABLE contract_payments MODIFY payment_method_type_id CHAR(2) NOT NULL;');
        }

        // income_payments
        if (Schema::hasTable('income_payments')) {
            Schema::table('income_payments', function (Blueprint $table) {
                if (Schema::hasColumn('income_payments', 'payment_method_id')) {
                    $table->dropForeign(['payment_method_id']);
                    $table->dropColumn('payment_method_id');
                }
            });
            DB::statement('ALTER TABLE income_payments MODIFY payment_method_type_id CHAR(2) NOT NULL;');
        }
    }
}
