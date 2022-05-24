<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use App\Criptografia\HashCpf;
use App\Models\Paciente;
use App\Models\Tenant;
use App\Tenancy\GerenciadorSchemas;
use Tests\TestCase;

class CriptografiaTest extends TestCase
{
    use DatabaseMigrations;

    public function testCpfFicaCriptografadoEComHash()
    {
        $tenant = Tenant::create(['nome' => 'Teste', 'slug' => 'teste', 'schema' => 'clinica_teste']);
        $schemas = app(GerenciadorSchemas::class);
        $schemas->criar($tenant);
        $schemas->usar($tenant);

        $paciente = Paciente::create(['nome' => 'Maria', 'cpf' => '529.982.247-25', 'telefone' => '11999999999']);
        $bruto = DB::table('pacientes')->where('id', $paciente->id)->value('cpf');

        $this->assertNotEquals('529.982.247-25', $bruto);
        $this->assertEquals('529.982.247-25', $paciente->fresh()->cpf);
        $this->assertEquals(HashCpf::gerar('52998224725'), $paciente->cpf_hash);

        $schemas->usarPublico();
    }
}
