<?php

namespace Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CriaClinica;
use Tests\TestCase;

class RelatorioFaltasTest extends TestCase
{
    use CriaClinica, RefreshDatabase;

    public function test_relatorio_conta_faltas_e_ignora_cancelados(): void
    {
        $clinica = $this->criarClinica('bem-estar');
        $profissional = $this->criarProfissional($clinica);
        $servico = $this->criarServico($clinica);
        $paciente = $this->criarPaciente($clinica);

        $this->criarAgendamento($clinica, $paciente, $profissional, $servico, CarbonImmutable::parse('2024-02-05 09:00'), 'realizado');
        $this->criarAgendamento($clinica, $paciente, $profissional, $servico, CarbonImmutable::parse('2024-02-06 09:00'), 'faltou');
        $this->criarAgendamento($clinica, $paciente, $profissional, $servico, CarbonImmutable::parse('2024-02-07 09:00'), 'cancelado');

        $this->artisan('relatorios:faltas', ['clinica' => 'bem-estar', '--mes' => '2024-02'])
            ->expectsTable(['Profissional', 'Atendimentos', 'Faltas', 'Taxa'], [['Dra. Teste', 2, 1, '50,0%']])
            ->assertSuccessful();
    }
}
