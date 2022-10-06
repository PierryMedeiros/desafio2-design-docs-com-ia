<?php
namespace App\Listeners;

use Illuminate\Support\Facades\Log;
use App\Events\AgendamentoStatusAlterado;

class RegistrarMudancaDeStatus
{
    public function handle(AgendamentoStatusAlterado $evento): void
    {
        Log::info('Status do agendamento alterado', [
            'agendamento_id' => $evento->agendamento->id,
            'tenant_id' => $evento->agendamento->tenant_id,
            'de' => $evento->statusAnterior,
            'para' => $evento->agendamento->status,
            'usuario_id' => auth()->id(),
        ]);
    }
}
