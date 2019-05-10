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
        foreach (Tenant::orderBy('id')->get() as $tenant) {
            $this->info("Migrando {$tenant->schema}...");

            $schemas->migrar($tenant);
        }

        $this->info('Pronto.');
    }
}
