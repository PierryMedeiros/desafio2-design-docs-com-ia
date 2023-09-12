<?php

namespace App\Lembretes\Canais;

use App\Lembretes\CanalLembrete;
use App\Lembretes\Telefone;
use App\Models\Paciente;
use Illuminate\Support\Facades\Http;

class WhatsAppCloud implements CanalLembrete
{
    public function __construct(private array $config) {}

    public function nome(): string
    {
        return 'whatsapp';
    }

    public function aceita(Paciente $paciente): bool
    {
        return $paciente->aceita_whatsapp && ! empty($paciente->telefone);
    }

    public function enviar(Paciente $paciente, string $mensagem): void
    {
        $url = sprintf(
            'https://graph.facebook.com/%s/%s/messages',
            $this->config['versao_api'],
            $this->config['phone_number_id']
        );

        Http::withToken($this->config['token'])
            ->timeout(10)
            ->post($url, [
                'messaging_product' => 'whatsapp',
                'to' => ltrim(Telefone::e164($paciente->telefone), '+'),
                'type' => 'template',
                'template' => [
                    'name' => $this->config['template'],
                    'language' => ['code' => 'pt_BR'],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => [
                                ['type' => 'text', 'text' => $mensagem],
                            ],
                        ],
                    ],
                ],
            ])
            ->throw();
    }
}
