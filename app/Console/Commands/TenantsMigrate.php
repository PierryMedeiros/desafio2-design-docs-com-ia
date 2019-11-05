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

        foreach ($tenants as $tenant) {
            $schemas->migrar($tenant);
            $barra->advance();
        }

        $barra->finish();
        $this->line('');
        $this->info($tenants->count().' schemas migrados.');
    }
}
