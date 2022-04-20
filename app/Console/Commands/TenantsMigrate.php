<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Tenant;
use App\Tenancy\GerenciadorSchemas;

class TenantsMigrate extends Command
{
    protected $signature = 'tenants:migrate';

    protected $description = 'Roda as migrations da clínica em todos os schemas';

    public function handle(GerenciadorSchemas $schemas)
    {
        $tenants = Tenant::orderBy('id')->get();
        $barra = $this->output->createProgressBar($tenants->count());
        $falhas = [];

        foreach ($tenants as $tenant) {
            $inicio = microtime(true);

            try {
                $schemas->migrar($tenant);
            } catch (\Throwable $e) {
                $falhas[$tenant->schema] = $e->getMessage();
                $schemas->usarPublico();
            }

            Log::info('tenants:migrate', [
                'schema' => $tenant->schema,
                'segundos' => round(microtime(true) - $inicio, 1),
                'ok' => !isset($falhas[$tenant->schema]),
            ]);

            $barra->advance();
        }

        $barra->finish();
        $this->line('');
        $this->info(($tenants->count() - count($falhas)).' schemas migrados.');

        foreach ($falhas as $schema => $erro) {
            $this->error("{$schema}: {$erro}");
        }

        return count($falhas) > 0 ? 1 : 0;
    }
}
