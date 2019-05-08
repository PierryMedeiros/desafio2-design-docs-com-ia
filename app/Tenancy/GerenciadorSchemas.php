<?php
namespace App\Tenancy;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant;

class GerenciadorSchemas
{
    private $atual;

    public function criar(Tenant $tenant)
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS "'.$tenant->schema.'"');

        $this->migrar($tenant);
    }

    public function migrar(Tenant $tenant)
    {
        $this->usar($tenant);

        Artisan::call('migrate', [
            '--path' => 'database/migrations/tenant',
            '--force' => true,
        ]);

        $this->usarPublico();
    }

    public function usar(Tenant $tenant)
    {
        $this->atual = $tenant;

        $this->trocarSchema([$tenant->schema, 'public']);
    }

    public function usarPublico()
    {
        $this->atual = null;

        $this->trocarSchema('public');
    }

    public function atual()
    {
        return $this->atual;
    }

    private function trocarSchema($schema)
    {
        config(['database.connections.pgsql.schema' => $schema]);

        DB::purge('pgsql');
        DB::reconnect('pgsql');
    }
}
