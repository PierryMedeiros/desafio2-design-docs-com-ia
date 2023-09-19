<?php

namespace Tests\Feature\Lembretes;

use App\Jobs\EnviarLembreteAgendamento;
use App\Lembretes\CanalLembrete;
use App\Lembretes\FabricaDeCanais;
use App\Models\Paciente;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\CriaClinica;
use Tests\TestCase;

class EnviarLembreteTest extends TestCase
{
    use CriaClinica, RefreshDatabase;

    public function test_job_envia_e_marca_o_lembrete(): void
    {
        $canal = new class implements CanalLembrete
        {
            public array $enviados = [];

            public function nome(): string
            {
                return 'fake';
            }

            public function aceita(Paciente $paciente): bool
            {
                return true;
            }

            public function enviar(Paciente $paciente, string $mensagem): void
            {
                $this->enviados[] = $mensagem;
            }
        };

        $clinica = $this->criarClinica();
        $agendamento = $this->criarAgendamento($clinica, $this->criarPaciente($clinica, ['nome' => 'Joana Souza']), $this->criarProfissional($clinica), $this->criarServico($clinica), CarbonImmutable::parse('2030-05-02 09:00'));

        (new EnviarLembreteAgendamento($agendamento->id))->handle($canal);

        $this->assertCount(1, $canal->enviados);
        $this->assertStringContainsString('Joana', $canal->enviados[0]);
        $this->assertStringContainsString('02/05 às 09:00', $canal->enviados[0]);
        $this->assertNotNull($agendamento->fresh()->lembrete_enviado_em);

        (new EnviarLembreteAgendamento($agendamento->id))->handle($canal);

        $this->assertCount(1, $canal->enviados);
    }

    public function test_paciente_sem_whatsapp_recebe_por_sms(): void
    {
        Log::spy();

        $clinica = $this->criarClinica();
        $paciente = $this->criarPaciente($clinica, ['aceita_whatsapp' => false]);
        $agendamento = $this->criarAgendamento($clinica, $paciente, $this->criarProfissional($clinica), $this->criarServico($clinica), CarbonImmutable::parse('2030-05-02 09:00'));

        EnviarLembreteAgendamento::dispatchSync($agendamento->id);

        Log::shouldHaveReceived('info')->withArgs(fn ($mensagem) => $mensagem === 'Lembrete enviado por sms (driver log)')->once();
        Log::shouldNotHaveReceived('info', ['Lembrete enviado por whatsapp (driver log)']);
    }

    public function test_falha_no_whatsapp_cai_para_o_sms(): void
    {
        config([
            'lembretes.whatsapp.driver' => 'cloud',
            'lembretes.whatsapp.token' => 'token',
            'lembretes.whatsapp.phone_number_id' => '123',
            'lembretes.sms.driver' => 'twilio',
            'lembretes.sms.sid' => 'AC123',
            'lembretes.sms.token' => 'segredo',
            'lembretes.sms.from' => '+15550001111',
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response(['error' => 'indisponivel'], 500),
            'api.twilio.com/*' => Http::response(['sid' => 'SM1'], 201),
        ]);

        $clinica = $this->criarClinica();
        $agendamento = $this->criarAgendamento($clinica, $this->criarPaciente($clinica), $this->criarProfissional($clinica), $this->criarServico($clinica), CarbonImmutable::parse('2030-05-02 09:00'));

        (new EnviarLembreteAgendamento($agendamento->id))->handle(app(FabricaDeCanais::class)->criar());

        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.twilio.com') && $request['To'] === '+5511987654321');
        $this->assertNotNull($agendamento->fresh()->lembrete_enviado_em);
    }
}
