<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsFavoriteToPersonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('persons')) {
            Schema::table('persons', function (Blueprint $table) {
                if (!Schema::hasColumn('persons', 'is_favorite')) {
                    $table->boolean('is_favorite')->default(false)->after('enabled');
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
        if (Schema::hasTable('persons')) {
            Schema::table('persons', function (Blueprint $table) {
                if (Schema::hasColumn('persons', 'is_favorite')) {
                    $table->dropColumn('is_favorite');
                }
            });
        }
    }
}
