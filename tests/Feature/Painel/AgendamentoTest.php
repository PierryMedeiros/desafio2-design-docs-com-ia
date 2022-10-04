<?php
namespace Tests\Feature\Painel;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Events\AgendamentoStatusAlterado;
use App\Models\Agendamento;
use Tests\CriaClinica;
use Tests\TestCase;

class AgendamentoTest extends TestCase
{
    use CriaClinica, RefreshDatabase;

    public function test_cria_agendamento_com_fim_pela_duracao_do_servico(): void
    {
        $clinica = $this->criarClinica();
        $profissional = $this->criarProfissional($clinica);
        $servico = $this->criarServico($clinica, 45);
        $paciente = $this->criarPaciente($clinica);
        $dia = $this->proximoDiaUtil();

        $this->actingAs($this->criarUsuario($clinica))
            ->post('/agendamentos', [
                'paciente_id' => $paciente->id,
                'profissional_id' => $profissional->id,
                'servico_id' => $servico->id,
                'data' => $dia->toDateString(),
                'hora' => '10:00',
                'convenio' => 'Plano Vida',
                'notas_clinicas' => 'Primeira consulta',
            ])
            ->assertRedirect('/agenda?data='.$dia->toDateString());

        $agendamento = Agendamento::first();

        $this->assertSame($clinica->id, $agendamento->tenant_id);
        $this->assertSame('10:45', $agendamento->fim->format('H:i'));
        $this->assertSame(Agendamento::AGENDADO, $agendamento->status);
        $this->assertSame('Plano Vida', $agendamento->convenio);
    }

    public function test_nao_cria_agendamento_em_horario_ocupado(): void
    {
        $clinica = $this->criarClinica();
        $profissional = $this->criarProfissional($clinica);
        $servico = $this->criarServico($clinica);
        $paciente = $this->criarPaciente($clinica);
        $dia = $this->proximoDiaUtil();

        $this->criarAgendamento($clinica, $paciente, $profissional, $servico, $dia->setTime(10, 0));

        $this->actingAs($this->criarUsuario($clinica))
            ->post('/agendamentos', [
                'paciente_id' => $paciente->id,
                'profissional_id' => $profissional->id,
                'servico_id' => $servico->id,
                'data' => $dia->toDateString(),
                'hora' => '10:15',
            ])
            ->assertSessionHasErrors('hora');

        $this->assertSame(1, Agendamento::count());
    }

    public function test_mudanca_de_status_dispara_evento(): void
    {
        Event::fake([AgendamentoStatusAlterado::class]);

        $clinica = $this->criarClinica();
        $agendamento = $this->criarAgendamento(
            $clinica,
            $this->criarPaciente($clinica),
            $this->criarProfissional($clinica),
            $this->criarServico($clinica),
            $this->proximoDiaUtil()->setTime(9, 0)
        );

        $this->actingAs($this->criarUsuario($clinica))
            ->patch("/agendamentos/{$agendamento->id}/status", ['status' => Agendamento::CONFIRMADO])
            ->assertRedirect();

        $this->assertSame(Agendamento::CONFIRMADO, $agendamento->fresh()->status);

        Event::assertDispatched(AgendamentoStatusAlterado::class, fn ($evento) => $evento->statusAnterior === Agendamento::AGENDADO);
    }
}
