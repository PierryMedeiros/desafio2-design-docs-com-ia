<?php

namespace App\Lembretes;

use App\Lembretes\Canais\LogCanal;
use App\Lembretes\Canais\SmsTwilio;
use App\Lembretes\Canais\WhatsAppCloud;
use InvalidArgumentException;

class FabricaDeCanais
{
    public function criar(): CanalLembrete
    {
        $canais = array_map(fn (string $nome) => $this->canal($nome), config('lembretes.canais'));

        return new CanalComFallback($canais);
    }

    public function canal(string $nome): CanalLembrete
    {
        $config = config("lembretes.{$nome}");

        $canal = match ($nome) {
            'whatsapp' => new WhatsAppCloud($config),
            'sms' => new SmsTwilio($config),
            default => throw new InvalidArgumentException("Canal de lembrete desconhecido: {$nome}"),
        };

        if (($config['driver'] ?? 'log') === 'log') {
            return new LogCanal($canal);
        }

        return $canal;
    }
}
