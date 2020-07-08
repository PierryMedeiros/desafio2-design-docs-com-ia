<?php
namespace App\Lembretes\Canais;

use Illuminate\Support\Facades\Log;
use App\Lembretes\CanalLembrete;
use App\Models\Paciente;

class LogCanal implements CanalLembrete
{
    private $canal;

    public function __construct(CanalLembrete $canal)
    {
        $this->canal = $canal;
    }

    public function nome()
    {
        return $this->canal->nome();
    }

    public function enviar(Paciente $paciente, $mensagem)
    {
        Log::info("Lembrete enviado por {$this->nome()} (driver log)", [
            'paciente_id' => $paciente->id,
            'telefone' => $paciente->telefone,
            'mensagem' => $mensagem,
        ]);
    }
}
