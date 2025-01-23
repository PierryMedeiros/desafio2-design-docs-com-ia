<?php

namespace Tests\Feature\Agenda;

use App\Agenda\Disponibilidade;
use App\Models\Bloqueio;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CriaClinica;
use Tests\TestCase;

class DisponibilidadeTest extends TestCase
{
    use CriaClinica, RefreshDatabase;

    public function test_horarios_respeitam_a_duracao_do_servico(): void
    {
        $clinica = $this->criarClinica();
        $profissional = $this->criarProfissional($clinica);
        $servico = $this->criarServico($clinica, 90);

        $horarios = (new Disponibilidade)->horariosLivres($profissional, $servico, CarbonImmutable::parse('2030-03-12'));

        $this->assertSame(['08:00', '09:30'], array_map(fn ($horario) => $horario->format('H:i'), $horarios));
    }

    public function test_horarios_que_ja_passaram_nao_aparecem(): void
    {
        $clinica = $this->criarClinica();
        $profissional = $this->criarProfissional($clinica);
        $servico = $this->criarServico($clinica, 60);
        $agora = CarbonImmutable::parse('2030-03-12 09:10');

        $horarios = (new Disponibilidade)->horariosLivres($profissional, $servico, $agora, $agora);

        $this->assertSame(['10:00', '11:00'], array_map(fn ($horario) => $horario->format('H:i'), $horarios));
    }

    public function test_feriado_e_bloqueio_da_clinica_zeram_o_dia(): void
    {
        $clinica = $this->criarClinica();
        $profissional = $this->criarProfissional($clinica);
        $disponibilidade = new Disponibilidade;

        $this->assertTrue($disponibilidade->diaBloqueado($profissional, CarbonImmutable::parse('2030-12-25')));

        Bloqueio::create(['tenant_id' => $clinica->id, 'profissional_id' => null, 'data' => '2030-03-13', 'motivo' => 'Dedetização']);

        $this->assertTrue($disponibilidade->diaBloqueado($profissional, CarbonImmutable::parse('2030-03-13')));
        $this->assertFalse($disponibilidade->diaBloqueado($profissional, CarbonImmutable::parse('2030-03-14')));
    }

    public function test_conflito_considera_sobreposicao(): void
    {
        $clinica = $this->criarClinica();
        $profissional = $this->criarProfissional($clinica);
        $servico = $this->criarServico($clinica);
        $agendamento = $this->criarAgendamento($clinica, $this->criarPaciente($clinica), $profissional, $servico, CarbonImmutable::parse('2030-03-12 09:00'));
        $disponibilidade = new Disponibilidade;

        $this->assertTrue($disponibilidade->conflita($profissional->id, CarbonImmutable::parse('2030-03-12 09:15'), CarbonImmutable::parse('2030-03-12 09:45')));
        $this->assertFalse($disponibilidade->conflita($profissional->id, CarbonImmutable::parse('2030-03-12 09:30'), CarbonImmutable::parse('2030-03-12 10:00')));
        $this->assertFalse($disponibilidade->conflita($profissional->id, CarbonImmutable::parse('2030-03-12 09:00'), CarbonImmutable::parse('2030-03-12 09:30'), $agendamento->id));
    }
}
