<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
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
            try {
                $schemas->migrar($tenant);
            } catch (\Throwable $e) {
                $falhas[$tenant->schema] = $e->getMessage();
                $schemas->usarPublico();
            }

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
