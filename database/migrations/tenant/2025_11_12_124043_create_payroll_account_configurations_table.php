<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePayrollAccountConfigurationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payroll_account_configurations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('salary_account', 10)->nullable();                // Sueldos
            $table->string('transportation_allowance_account', 10)->nullable(); // Auxilio de transporte
            $table->string('health_account', 10)->nullable();                // Salud (EPS)
            $table->string('pension_account', 10)->nullable();               // Pensión (AFP)
            $table->string('vacation_account', 10)->nullable();              // Vacaciones
            $table->string('service_bonus_account', 10)->nullable();         // Prima de servicios
            $table->string('extra_service_bonus_account', 10)->nullable();   // Prima extralegales
            $table->string('severance_account', 10)->nullable();             // Cesantías
            $table->string('severance_interest_account', 10)->nullable();    // Intereses de cesantías
            $table->string('other_bonuses_account', 10)->nullable();         // Otras bonificaciones
            $table->string('net_payable_account', 10)->nullable();           // Salarios por pagar (nómina por pagar)
            $table->timestamps();
        });
        \DB::table('payroll_account_configurations')->insert([
            'salary_account' => '510506',
            'health_account' => '237005',
            'pension_account' => '238030',
            'transportation_allowance_account' => '510527',
            'vacation_account' => '510539',
            'service_bonus_account' => '510536',
            'extra_service_bonus_account' => '510542',
            'severance_account' => '510530',
            'severance_interest_account' => '510533',
            'other_bonuses_account' => null,
            'net_payable_account' => '250505',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payroll_account_configurations');
    }
}
