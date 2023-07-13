<?php

namespace App\Lembretes\Canais;

use App\Lembretes\CanalLembrete;
use App\Lembretes\Telefone;
use App\Models\Paciente;
use Illuminate\Support\Facades\Http;

class SmsTwilio implements CanalLembrete
{
    public function __construct(private array $config) {}

    public function nome(): string
    {
        return 'sms';
    }

    public function enviar(Paciente $paciente, string $mensagem): void
    {
        if (! $paciente->telefone) {
            return;
        }

        $sid = $this->config['sid'];

        Http::asForm()
            ->withBasicAuth($sid, $this->config['token'])
            ->timeout(10)
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => $this->config['from'],
                'To' => Telefone::e164($paciente->telefone),
                'Body' => $mensagem,
            ])
            ->throw();
    }
}
