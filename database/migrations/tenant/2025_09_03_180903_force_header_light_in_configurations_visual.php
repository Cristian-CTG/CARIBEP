<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ForceHeaderLightInConfigurationsVisual extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        DB::beginTransaction();

        try {
            DB::table('configurations')
                ->select('id', 'visual')
                ->orderBy('id')
                ->chunkById(200, function ($rows) {
                    foreach ($rows as $row) {
                        $data = json_decode($row->visual, true) ?? [];
                        $data['header'] = 'light';

                        DB::table('configurations')
                            ->where('id', $row->id)
                            ->update([
                                'visual' => json_encode($data, JSON_UNESCAPED_UNICODE)
                            ]);
                    }
                });

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        DB::table('configurations')
            ->select('id', 'visual')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $data = json_decode($row->visual, true) ?? [];

                    if (isset($data['header'])) {
                        unset($data['header']);
                    }

                    DB::table('configurations')
                        ->where('id', $row->id)
                        ->update([
                            'visual' => json_encode($data, JSON_UNESCAPED_UNICODE)
                        ]);
                }
            });
    }
}
