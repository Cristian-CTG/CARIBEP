<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddObservationToCoRemissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('co_remissions')) {
            Schema::table('co_remissions', function (Blueprint $table) {
                if (!Schema::hasColumn('co_remissions', 'observation')) {
                    $table->text('observation')->nullable()->after('date_expiration');
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
        if (Schema::hasTable('co_remissions')) {
            Schema::table('co_remissions', function (Blueprint $table) {
                if (Schema::hasColumn('co_remissions', 'observation')) {
                    $table->dropColumn('observation');
                }
            });
        }
    }
}
