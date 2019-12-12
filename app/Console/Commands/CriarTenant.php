<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\GerenciadorSchemas;

class CriarTenant extends Command
{
    protected $signature = 'tenants:criar {nome} {email}';

    protected $description = 'Cria uma clínica nova com o schema e o usuário admin';

    public function handle(GerenciadorSchemas $schemas)
    {
        $slug = Str::slug($this->argument('nome'));
        $senha = Str::random(10);

        $tenant = Tenant::create([
            'nome' => $this->argument('nome'),
            'slug' => $slug,
            'schema' => 'clinica_'.str_replace('-', '_', $slug),
        ]);

        $schemas->criar($tenant);

        User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Administrador',
            'email' => $this->argument('email'),
            'password' => Hash::make($senha),
            'papel' => User::PAPEL_ADMIN,
        ]);

        $this->info("Clínica {$tenant->nome} criada no schema {$tenant->schema}.");
        $this->info("Senha do admin: {$senha}");
    }
}
