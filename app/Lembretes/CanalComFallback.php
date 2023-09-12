<?php

namespace App\Lembretes;

use App\Models\Paciente;
use Illuminate\Support\Facades\Log;
use Throwable;

class CanalComFallback implements CanalLembrete
{
    /**
     * @param  array<int, CanalLembrete>  $canais
     */
    public function __construct(private array $canais) {}

    public function nome(): string
    {
        return implode('>', array_map(fn (CanalLembrete $canal) => $canal->nome(), $this->canais));
    }

    public function aceita(Paciente $paciente): bool
    {
        foreach ($this->canais as $canal) {
            if ($canal->aceita($paciente)) {
                return true;
            }
        }

        return false;
    }

    public function enviar(Paciente $paciente, string $mensagem): void
    {
        $ultimaFalha = null;

        foreach ($this->canais as $canal) {
            if (! $canal->aceita($paciente)) {
                continue;
            }

            try {
                $canal->enviar($paciente, $mensagem);

                return;
            } catch (Throwable $e) {
                $ultimaFalha = $e;

                Log::warning("Falha ao enviar lembrete por {$canal->nome()}", [
                    'paciente_id' => $paciente->id,
                    'erro' => $e->getMessage(),
                ]);
            }
        }

        throw new FalhaNoEnvioDoLembrete(
            "Nenhum canal conseguiu enviar o lembrete do paciente {$paciente->id}.",
            0,
            $ultimaFalha
        );
    }
}
