<?php
namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\CriaClinica;
use Tests\TestCase;

class HorariosTest extends TestCase
{
    use CriaClinica, RefreshDatabase;

    public function test_lista_horarios_livres(): void
    {
        $clinica = $this->criarClinica();
        $profissional = $this->criarProfissional($clinica);
        $servico = $this->criarServico($clinica, 60);
        $paciente = $this->criarPaciente($clinica);
        $dia = $this->proximoDiaUtil();

        $this->criarAgendamento($clinica, $paciente, $profissional, $servico, $dia->setTime(9, 0));

        Sanctum::actingAs($paciente);

        $this->getJson("/api/v1/horarios?profissional_id={$profissional->id}&servico_id={$servico->id}&data={$dia->toDateString()}")
            ->assertOk()
            ->assertJsonPath('horarios', ['08:00', '10:00', '11:00']);
    }
}
