<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Queue;
use App\Jobs\EnviarLembreteAgendamento;
use App\Models\Agendamento;
use App\Models\Paciente;
use App\Models\Profissional;
use App\Models\Servico;
use App\Models\Tenant;
use App\Tenancy\GerenciadorSchemas;
use Tests\TestCase;

class LembretesTest extends TestCase
{
    use DatabaseMigrations;

    public function testEnfileiraLembreteDaConsultaDeAmanha()
    {
        Queue::fake();

        $tenant = Tenant::create(['nome' => 'Teste', 'slug' => 'teste', 'schema' => 'clinica_teste']);
        $schemas = app(GerenciadorSchemas::class);
        $schemas->criar($tenant);
        $schemas->usar($tenant);

        $paciente = Paciente::create(['nome' => 'Maria', 'telefone' => '11999999999', 'email' => 'maria@teste.test']);
        $profissional = Profissional::create(['nome' => 'Dra. Ana']);
        $servico = Servico::create(['nome' => 'Consulta', 'duracao_minutos' => 30]);

        $agendamento = Agendamento::create([
            'paciente_id' => $paciente->id,
            'profissional_id' => $profissional->id,
            'servico_id' => $servico->id,
            'inicio' => now()->addHours(5),
            'fim' => now()->addHours(5)->addMinutes(30),
            'status' => Agendamento::AGENDADO,
        ]);

        $schemas->usarPublico();

        $this->artisan('lembretes:enfileirar')->assertExitCode(0);

        Queue::assertPushedOn('notificacoes', EnviarLembreteAgendamento::class, function ($job) use ($agendamento) {
            return $job->agendamentoId === $agendamento->id;
        });
    }
}
