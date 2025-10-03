<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Modules\RadianEvent\Http\Controllers\SearchEmailController;
use Modules\Factcolombia1\Models\TenantService\AdvancedConfiguration;
class ProcessRadianEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'radian:process-emails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Procesa automáticamente los correos de RADIAN (Gmail)';

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
        $this->info('Iniciando procesamiento de correos...');

        // Validar configuración antes de ejecutar
        $config = AdvancedConfiguration::first();
        if (!$config || !$config->auto_process_radian_emails) {
            $this->info('Procesamiento automático de correos RADIAN desactivado para este tenant.');
            return 0;
        }
        // Puedes ajustar las fechas aquí, por ejemplo, para procesar el día anterior
        $start = now()->subDay()->format('Y-m-d');
        $end = now()->subDay()->format('Y-m-d');

        $request = new Request([
            'search_start_date' => $start,
            'search_end_date' => $end,
        ]);

        $controller = new SearchEmailController();
        $result = $controller->searchImapEmails($request);

        if (isset($result['success']) && $result['success']) {
            $this->info($result['message']);
        } else {
            $this->error($result['message'] ?? 'Error al procesar correos');
        }
    }
}
