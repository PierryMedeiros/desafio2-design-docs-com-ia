<?php

namespace Tests\Feature\Lembretes;

use App\Jobs\EnviarLembreteAgendamento;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\CriaClinica;
use Tests\TestCase;

class EnfileirarLembretesTest extends TestCase
{
    use CriaClinica, RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    private function congelar(string $quando, string $fuso): void
    {
        $momento = CarbonImmutable::parse($quando, $fuso);

        Carbon::setTestNow($momento);
        CarbonImmutable::setTestNow($momento);
    }

    public function test_enfileira_so_agendamentos_das_proximas_24_horas(): void
    {
        Queue::fake();
        $this->congelar('2025-03-10 07:00', 'America/Sao_Paulo');

        $clinica = $this->criarClinica();
        $profissional = $this->criarProfissional($clinica);
        $servico = $this->criarServico($clinica);
        $paciente = $this->criarPaciente($clinica);

        $amanhaCedo = $this->criarAgendamento($clinica, $paciente, $profissional, $servico, CarbonImmutable::parse('2025-03-11 06:30'));
        $this->criarAgendamento($clinica, $paciente, $profissional, $servico, CarbonImmutable::parse('2025-03-11 09:00'));
        $this->criarAgendamento($clinica, $paciente, $profissional, $servico, CarbonImmutable::parse('2025-03-10 10:00'), 'cancelado');

        $this->artisan('lembretes:enfileirar')->assertSuccessful();

        Queue::assertPushedOn('notificacoes', EnviarLembreteAgendamento::class);
        Queue::assertPushed(EnviarLembreteAgendamento::class, 1);
        Queue::assertPushed(EnviarLembreteAgendamento::class, fn ($job) => $job->agendamentoId === $amanhaCedo->id);
    }

    public function test_janela_usa_o_fuso_da_clinica(): void
    {
        Queue::fake();
        $this->congelar('2025-03-10 10:00', 'UTC');

        $clinica = $this->criarClinica();
        $profissional = $this->criarProfissional($clinica);
        $servico = $this->criarServico($clinica);
        $paciente = $this->criarPaciente($clinica);

        $this->criarAgendamento($clinica, $paciente, $profissional, $servico, CarbonImmutable::parse('2025-03-11 09:30'));

        $this->artisan('lembretes:enfileirar')->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_nao_enfileira_duas_vezes_o_mesmo_agendamento(): void
    {
        Queue::fake();

        $clinica = $this->criarClinica();
        $this->criarAgendamento($clinica, $this->criarPaciente($clinica), $this->criarProfissional($clinica), $this->criarServico($clinica), CarbonImmutable::now('America/Sao_Paulo')->addHours(2));

        $this->artisan('lembretes:enfileirar');
        $this->artisan('lembretes:enfileirar');

        Queue::assertPushed(EnviarLembreteAgendamento::class, 1);
    }
}
