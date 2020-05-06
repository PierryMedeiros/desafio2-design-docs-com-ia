<?php
namespace Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use App\Lembretes\Canais\SmsTwilio;
use App\Models\Paciente;
use Tests\TestCase;

class SmsTwilioTest extends TestCase
{
    public function testEnviaSmsPelaApiDoTwilio()
    {
        $historico = [];
        $pilha = HandlerStack::create(new MockHandler([new Response(201, [], '{"sid":"SM1"}')]));
        $pilha->push(Middleware::history($historico));

        $canal = new SmsTwilio(new Client(['handler' => $pilha]), [
            'sid' => 'AC123',
            'token' => 'segredo',
            'from' => '+15550001111',
        ]);

        $canal->enviar(new Paciente(['telefone' => '11987654321']), 'Lembrete');

        $this->assertCount(1, $historico);
        $this->assertStringContainsString('/Accounts/AC123/Messages.json', (string) $historico[0]['request']->getUri());
        parse_str((string) $historico[0]['request']->getBody(), $corpo);
        $this->assertSame('+5511987654321', $corpo['To']);
        $this->assertSame('Lembrete', $corpo['Body']);
    }

    public function testPacienteSemTelefoneNaoEnvia()
    {
        $historico = [];
        $pilha = HandlerStack::create(new MockHandler([]));
        $pilha->push(Middleware::history($historico));

        $canal = new SmsTwilio(new Client(['handler' => $pilha]), ['sid' => 'AC123', 'token' => 'x', 'from' => '+1']);
        $canal->enviar(new Paciente(['telefone' => null]), 'Lembrete');

        $this->assertCount(0, $historico);
    }
}
