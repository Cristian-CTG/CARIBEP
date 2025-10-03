<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\RadianEvent\Http\Controllers\RadianEventController;
use Illuminate\Http\Request;
use Modules\Factcolombia1\Models\TenantService\AdvancedConfiguration;
class ProcessRadianEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'radian:process-events';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Procesa automáticamente todos los eventos pendientes de RADIAN';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Iniciando procesamiento automático de eventos RADIAN...');

        // Validar configuración antes de ejecutar
        $config = AdvancedConfiguration::first();
        if (!$config || !$config->auto_process_radian_events) {
            $this->info('Procesamiento automático de eventos RADIAN desactivado para este tenant.');
            return 0;
        }
        $controller = new RadianEventController();
        $request = new Request(['all_pending' => true]);
        $result = $controller->runEvent($request);

        if (isset($result['success']) && $result['success']) {
            $this->info($result['message']);
        } else {
            $this->error($result['message'] ?? 'Error al procesar eventos');
        }
    }
}
