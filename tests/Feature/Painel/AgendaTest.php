<?php

namespace Tests\Feature\Painel;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CriaClinica;
use Tests\TestCase;

class AgendaTest extends TestCase
{
    use CriaClinica, RefreshDatabase;

    public function test_agenda_mostra_os_agendamentos_do_dia(): void
    {
        $clinica = $this->criarClinica();
        $profissional = $this->criarProfissional($clinica);
        $servico = $this->criarServico($clinica);
        $paciente = $this->criarPaciente($clinica, ['nome' => 'Maria Aparecida']);
        $dia = $this->proximoDiaUtil();

        $this->criarAgendamento($clinica, $paciente, $profissional, $servico, $dia->setTime(9, 0));

        $this->actingAs($this->criarUsuario($clinica))
            ->get('/agenda?data='.$dia->toDateString())
            ->assertOk()
            ->assertSee('Maria Aparecida')
            ->assertSee('09:00');
    }

    public function test_filtro_por_profissional(): void
    {
        $clinica = $this->criarClinica();
        $carla = $this->criarProfissional($clinica);
        $pedro = $this->criarProfissional($clinica);
        $servico = $this->criarServico($clinica);
        $dia = $this->proximoDiaUtil();

        $this->criarAgendamento($clinica, $this->criarPaciente($clinica, ['nome' => 'Paciente da Carla']), $carla, $servico, $dia->setTime(9, 0));
        $this->criarAgendamento($clinica, $this->criarPaciente($clinica, ['nome' => 'Paciente do Pedro', 'email' => 'pedro@x.test']), $pedro, $servico, $dia->setTime(10, 0));

        $this->actingAs($this->criarUsuario($clinica))
            ->get('/agenda?data='.$dia->toDateString().'&profissional_id='.$carla->id)
            ->assertOk()
            ->assertSee('Paciente da Carla')
            ->assertDontSee('Paciente do Pedro');
    }
}
