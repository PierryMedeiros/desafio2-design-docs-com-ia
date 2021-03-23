<?php
namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use App\Models\Agendamento;
use App\Models\Disponibilidade;
use App\Models\Paciente;
use App\Models\Profissional;
use App\Models\Servico;
use App\Models\Tenant;
use App\Tenancy\GerenciadorSchemas;
use Tests\TestCase;

class AgendamentosTest extends TestCase
{
    use DatabaseMigrations;

    private $paciente;

    private $profissional;

    private $servico;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::create(['nome' => 'Teste', 'slug' => 'teste', 'schema' => 'clinica_teste']);
        $schemas = app(GerenciadorSchemas::class);
        $schemas->criar($tenant);
        $schemas->usar($tenant);

        $this->paciente = Paciente::create([
            'nome' => 'Maria',
            'telefone' => '11999999999',
            'email' => 'maria@teste.test',
            'senha' => Hash::make('segredo'),
        ]);
        $this->profissional = Profissional::create(['nome' => 'Dra. Ana']);
        $this->servico = Servico::create(['nome' => 'Consulta', 'duracao_minutos' => 30]);

        foreach (range(0, 6) as $dia) {
            Disponibilidade::create([
                'profissional_id' => $this->profissional->id,
                'dia_semana' => $dia,
                'hora_inicio' => '08:00',
                'hora_fim' => '12:00',
            ]);
        }
    }

    public function testEmiteToken()
    {
        $this->postJson('/api/v1/auth/token', [
            'email' => 'maria@teste.test',
            'senha' => 'segredo',
            'clinica' => 'teste',
        ])->assertStatus(201)->assertJsonStructure(['token']);
    }

    public function testListaHorariosLivres()
    {
        Sanctum::actingAs($this->paciente);
        $amanha = now()->addDay()->toDateString();

        $this->withHeaders(['X-Clinica' => 'teste'])
            ->getJson("/api/v1/horarios?profissional_id={$this->profissional->id}&servico_id={$this->servico->id}&data={$amanha}")
            ->assertOk()
            ->assertJsonPath('horarios.0', '08:00');
    }

    public function testCriaECancelaAgendamento()
    {
        Sanctum::actingAs($this->paciente);
        $amanha = now()->addDay()->toDateString();

        $id = $this->withHeaders(['X-Clinica' => 'teste'])
            ->postJson('/api/v1/agendamentos', [
                'profissional_id' => $this->profissional->id,
                'servico_id' => $this->servico->id,
                'inicio' => $amanha.' 09:00',
            ])
            ->assertStatus(201)
            ->json('data.id');

        $this->withHeaders(['X-Clinica' => 'teste'])
            ->deleteJson("/api/v1/agendamentos/{$id}")
            ->assertNoContent();

        $this->assertEquals(Agendamento::CANCELADO, Agendamento::find($id)->status);
    }
}
