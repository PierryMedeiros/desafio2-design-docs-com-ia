<?php
namespace App\Lembretes\Canais;

use GuzzleHttp\Client;
use App\Lembretes\CanalLembrete;
use App\Lembretes\Telefone;
use App\Models\Paciente;

class SmsTwilio implements CanalLembrete
{
    private $http;

    private $config;

    public function __construct(Client $http, array $config)
    {
        $this->http = $http;
        $this->config = $config;
    }

    public function nome()
    {
        return 'sms';
    }

    public function enviar(Paciente $paciente, $mensagem)
    {
        if (!$paciente->telefone) {
            return;
        }

        $sid = $this->config['sid'];

        $this->http->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
            'auth' => [$sid, $this->config['token']],
            'form_params' => [
                'From' => $this->config['from'],
                'To' => Telefone::e164($paciente->telefone),
                'Body' => $mensagem,
            ],
            'timeout' => 10,
        ]);
    }
}
