<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTotalCanceledToCoSupportDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('co_support_documents', function (Blueprint $table) {
            $table->boolean('total_canceled')->default(false)->after('total');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('co_support_documents', function (Blueprint $table) {
            $table->dropColumn('total_canceled');
        });
    }
}
