<?php
namespace App\Lembretes\Canais;

use Illuminate\Support\Facades\Mail;
use App\Lembretes\CanalLembrete;
use App\Models\Paciente;

class EmailCanal implements CanalLembrete
{
    public function nome()
    {
        return 'email';
    }

    public function enviar(Paciente $paciente, $mensagem)
    {
        if (!$paciente->email) {
            return;
        }

        Mail::raw($mensagem, function ($email) use ($paciente) {
            $email->to($paciente->email)->subject('Lembrete da sua consulta');
        });
    }
}
