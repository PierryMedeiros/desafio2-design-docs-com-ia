<?php

namespace App\Lembretes;

use App\Models\Paciente;

interface CanalLembrete
{
    public function nome();

    public function enviar(Paciente $paciente, $mensagem);
}
