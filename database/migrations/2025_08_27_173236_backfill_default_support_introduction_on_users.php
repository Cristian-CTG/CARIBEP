<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class BackfillDefaultSupportIntroductionOnUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
    $intro = <<<'HTML'
<p><strong>¿Cómo podemos ayudarte?</strong></p>
<p>Nuestro equipo de soporte técnico está listo para resolver todas tus dudas sobre facturación electrónica, configuración del sistema y más.</p>
<p><strong>⏰Horarios de Atención</strong></p>
<p><strong>Lunes a Viernes:</strong> 9:00 AM - 6:00 PM</p>
<p><strong>Sábados:</strong> 9:00 AM - 1:00 PM</p>
HTML;
    
        DB::beginTransaction();
        try {
            DB::table('users')
                ->whereNull('introduction')
                ->orWhere('introduction', '')
                ->update(['introduction' => $intro]);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    public function down(): void
    {
    $intro = <<<'HTML'
<p><strong>¿Cómo podemos ayudarte?</strong></p>
<p>Nuestro equipo de soporte técnico está listo para resolver todas tus dudas sobre facturación electrónica, configuración del sistema y más.</p>
<p><strong>⏰Horarios de Atención</strong></p>
<p><strong>Lunes a Viernes:</strong> 9:00 AM - 6:00 PM</p>
<p><strong>Sábados:</strong> 9:00 AM - 1:00 PM</p>
HTML;
    
        DB::beginTransaction();
        try {
            DB::table('users')
                ->where('introduction', $intro)
                ->update(['introduction' => null]);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
