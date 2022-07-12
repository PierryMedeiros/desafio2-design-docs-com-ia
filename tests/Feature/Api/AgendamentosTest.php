<?php
namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use App\Models\Agendamento;
use Tests\CriaClinica;
use Tests\TestCase;

class AgendamentosTest extends TestCase
{
    use CriaClinica, RefreshDatabase;

    public function test_fluxo_de_agendamento_pelo_app(): void
    {
        $clinica = $this->criarClinica();
        $profissional = $this->criarProfissional($clinica);
        $servico = $this->criarServico($clinica);
        $paciente = $this->criarPaciente($clinica);
        $dia = $this->proximoDiaUtil();

        Sanctum::actingAs($paciente);

        $id = $this->postJson('/api/v1/agendamentos', [
            'profissional_id' => $profissional->id,
            'servico_id' => $servico->id,
            'inicio' => $dia->toDateString().' 09:00',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'agendado')
            ->assertJsonPath('data.profissional.id', $profissional->id)
            ->json('data.id');

        $this->getJson('/api/v1/agendamentos')
            ->assertOk()
            ->assertJsonPath('data.0.id', $id);

        $this->deleteJson("/api/v1/agendamentos/{$id}")->assertNoContent();

        $this->assertSame(Agendamento::CANCELADO, Agendamento::find($id)->status);
    }

    public function test_convenio_e_opcional_e_volta_no_agendamento(): void
    {
        $clinica = $this->criarClinica();
        $profissional = $this->criarProfissional($clinica);
        $servico = $this->criarServico($clinica);
        $dia = $this->proximoDiaUtil();

        Sanctum::actingAs($this->criarPaciente($clinica));

        $this->postJson('/api/v1/agendamentos', [
            'profissional_id' => $profissional->id,
            'servico_id' => $servico->id,
            'inicio' => $dia->toDateString().' 09:00',
            'convenio' => 'Plano Vida',
        ])
            ->assertCreated()
            ->assertJsonPath('data.convenio', 'Plano Vida');

        $this->postJson('/api/v1/agendamentos', [
            'profissional_id' => $profissional->id,
            'servico_id' => $servico->id,
            'inicio' => $dia->toDateString().' 10:00',
        ])
            ->assertCreated()
            ->assertJsonPath('data.convenio', null);

        $this->assertSame(1, Agendamento::where('convenio', 'Plano Vida')->count());
    }

    public function test_horario_ocupado_responde_409(): void
    {
        $clinica = $this->criarClinica();
        $profissional = $this->criarProfissional($clinica);
        $servico = $this->criarServico($clinica);
        $outro = $this->criarPaciente($clinica, ['email' => 'outro@x.test']);
        $dia = $this->proximoDiaUtil();

        $this->criarAgendamento($clinica, $outro, $profissional, $servico, $dia->setTime(9, 0));

        Sanctum::actingAs($this->criarPaciente($clinica));

        $this->postJson('/api/v1/agendamentos', [
            'profissional_id' => $profissional->id,
            'servico_id' => $servico->id,
            'inicio' => $dia->toDateString().' 09:00',
        ])->assertStatus(409);
    }
}
