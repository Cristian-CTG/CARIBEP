<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateJournalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('journal_prefixes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('prefix')->unique(); // Código del prefijo (Ej: FV, FC)
            $table->string('description'); // Descripción (Ej: Factura de Venta)
            $table->boolean('modifiable')->default(false); // Si el usuario puede modificarlo
            $table->timestamps();
        });

        // Insertar prefijos base
        DB::table('journal_prefixes')->insert([
            ['prefix' => 'FV', 'description' => 'Factura de Venta', 'modifiable' => false],
            ['prefix' => 'FC', 'description' => 'Factura de Compra', 'modifiable' => false],
            ['prefix' => 'RC', 'description' => 'Recibo de Caja', 'modifiable' => false],
            ['prefix' => 'RP', 'description' => 'Recibo de Pago', 'modifiable' => false],
            ['prefix' => 'TB', 'description' => 'Traslado de Bancos', 'modifiable' => false],
            ['prefix' => 'NM', 'description' => 'Nómina', 'modifiable' => false],
            ['prefix' => 'NC', 'description' => 'Asiento Contable Manual', 'modifiable' => false],
            ['prefix' => 'AJ', 'description' => 'Ajustes Contables', 'modifiable' => true],
            ['prefix' => 'DP', 'description' => 'Depreciaciones', 'modifiable' => true],
            ['prefix' => 'CC', 'description' => 'Cierre Contable', 'modifiable' => true],
        ]);

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('journal_prefix_id'); // ID prefijo
            $table->date('date'); // Fecha del asiento
            $table->text('description'); // Descripción del asiento
            $table->enum('status', ['draft', 'pending_approval', 'posted', 'rejected'])->default('draft'); // Borrador o publicado
            $table->timestamps();

            $table->foreign('journal_prefix_id')->references('id')->on('journal_prefixes');
        });

        Schema::create('journal_entry_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('journal_entry_id'); // ID del asiento contable
            $table->unsignedBigInteger('chart_of_account_id'); // ID de la cuenta contable
            $table->decimal('debit', 15, 2)->default(0); // Monto en débito
            $table->decimal('credit', 15, 2)->default(0); // Monto en crédito
            $table->timestamps();

            // Relaciones
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries');
            $table->foreign('chart_of_account_id')->references('id')->on('chart_of_accounts');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Eliminar claves foráneas de journal_entry_details
        Schema::table('journal_entry_details', function (Blueprint $table) {
            $table->dropForeign(['journal_entry_id']);
            $table->dropForeign(['chart_of_account_id']);
        });

        // Eliminar claves foráneas de journal_entries
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropForeign(['journal_prefix_id']);
        });

        Schema::dropIfExists('journal_prefixes');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('journal_entry_details');
    }
}
