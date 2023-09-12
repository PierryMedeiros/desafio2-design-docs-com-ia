<?php

namespace App\Lembretes;

use App\Models\Paciente;

interface CanalLembrete
{
    public function nome(): string;

    public function aceita(Paciente $paciente): bool;

    public function enviar(Paciente $paciente, string $mensagem): void;
}
