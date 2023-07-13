<?php

namespace App\Lembretes\Canais;

use App\Lembretes\CanalLembrete;
use App\Models\Paciente;
use Illuminate\Support\Facades\Log;

class LogCanal implements CanalLembrete
{
    public function __construct(private CanalLembrete $canal) {}

    public function nome(): string
    {
        return $this->canal->nome();
    }

    public function enviar(Paciente $paciente, string $mensagem): void
    {
        Log::info("Lembrete enviado por {$this->nome()} (driver log)", [
            'paciente_id' => $paciente->id,
            'telefone' => $paciente->telefone,
            'mensagem' => $mensagem,
        ]);
    }
}
