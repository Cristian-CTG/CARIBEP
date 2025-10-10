<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class UpdateVisualSidebarInConfigurationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $rows = DB::table('configurations')->whereNotNull('visual')->get();

        foreach ($rows as $row) {
            $visual = json_decode($row->visual, true);

            if (is_array($visual) && isset($visual['sidebars'])) {
                if ($visual['sidebars'] === 'dark') {
                    $visual['sidebars'] = 'light';

                    DB::table('configurations')
                        ->where('id', $row->id)
                        ->update([
                            'visual' => json_encode($visual, JSON_UNESCAPED_UNICODE)
                        ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
}
