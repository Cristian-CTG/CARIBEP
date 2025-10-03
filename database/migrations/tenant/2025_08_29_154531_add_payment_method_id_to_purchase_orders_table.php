<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddPaymentMethodIdToPurchaseOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Cambia la columna a NULL usando SQL directo para evitar error de Doctrine
        DB::statement('ALTER TABLE purchase_orders MODIFY payment_method_type_id CHAR(2) NULL;');

        Schema::table('purchase_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_orders', 'payment_method_id')) {
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
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_orders', 'payment_method_id')) {
                $table->dropForeign(['payment_method_id']);
                $table->dropColumn('payment_method_id');
            }
        });

        // Vuelve a poner la columna como NOT NULL si lo necesitas
        DB::statement('ALTER TABLE purchase_orders MODIFY payment_method_type_id CHAR(2) NOT NULL;');
    }
}
