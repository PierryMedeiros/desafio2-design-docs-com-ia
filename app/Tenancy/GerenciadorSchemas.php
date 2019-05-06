<?php
namespace App\Tenancy;

use Illuminate\Support\Facades\DB;
use App\Models\Tenant;

class GerenciadorSchemas
{
    public function criar(Tenant $tenant)
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS "'.$tenant->schema.'"');
    }

    public function usar(Tenant $tenant)
    {
        // TODO: trocar o search_path da conexao
    }
}
