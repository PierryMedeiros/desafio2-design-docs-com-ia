<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\Agendamento;
use App\Models\Anexo;
use App\Models\Paciente;
use App\Models\Profissional;
use App\Models\Servico;
use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\GerenciadorSchemas;
use Tests\TestCase;

class AnexoTest extends TestCase
{
    use DatabaseMigrations;

    public function testUploadGravaNoS3()
    {
        Storage::fake('s3');

        $tenant = Tenant::create(['nome' => 'Teste', 'slug' => 'teste', 'schema' => 'clinica_teste']);
        $usuario = User::factory()->create(['tenant_id' => $tenant->id]);
        $schemas = app(GerenciadorSchemas::class);
        $schemas->criar($tenant);
        $schemas->usar($tenant);

        $agendamento = Agendamento::create([
            'paciente_id' => Paciente::create(['nome' => 'Maria', 'telefone' => '11999999999'])->id,
            'profissional_id' => Profissional::create(['nome' => 'Dra. Ana'])->id,
            'servico_id' => Servico::create(['nome' => 'Consulta', 'duracao_minutos' => 30])->id,
            'inicio' => now()->addDay(),
            'fim' => now()->addDay()->addMinutes(30),
            'status' => Agendamento::AGENDADO,
        ]);

        $this->actingAs($usuario)
            ->post("/agendamentos/{$agendamento->id}/anexos", [
                'arquivo' => UploadedFile::fake()->create('exame.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect();

        $anexo = Anexo::first();

        $this->assertStringStartsWith("tenants/{$tenant->id}/agendamentos/{$agendamento->id}/", $anexo->caminho);
        Storage::disk('s3')->assertExists($anexo->caminho);
    }
}
