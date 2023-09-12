<?php

namespace App\Lembretes\Canais;

use App\Lembretes\CanalLembrete;
use App\Models\Paciente;
use Illuminate\Support\Facades\Mail;

class EmailCanal implements CanalLembrete
{
    public function nome(): string
    {
        return 'email';
    }

    public function aceita(Paciente $paciente): bool
    {
        return ! empty($paciente->email);
    }

    public function enviar(Paciente $paciente, string $mensagem): void
    {
        if (! $paciente->email) {
            return;
        }

        Mail::raw($mensagem, function ($email) use ($paciente) {
            $email->to($paciente->email)->subject('Lembrete da sua consulta');
        });
    }
}
