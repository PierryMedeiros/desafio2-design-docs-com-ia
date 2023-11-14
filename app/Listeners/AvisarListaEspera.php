<?php

namespace App\Listeners;

use App\Events\AgendamentoStatusAlterado;
use App\Models\Agendamento;
use App\Models\ListaEspera;
use Illuminate\Support\Facades\Log;

class AvisarListaEspera
{
    public function handle(AgendamentoStatusAlterado $evento): void
    {
        $agendamento = $evento->agendamento;

        if ($agendamento->status !== Agendamento::CANCELADO) {
            return;
        }

        $entradas = ListaEspera::query()
            ->where('tenant_id', $agendamento->tenant_id)
            ->whereNull('avisado_em')
            ->whereDate('data_desejada', $agendamento->inicio->toDateString())
            ->where(function ($query) use ($agendamento) {
                $query->whereNull('profissional_id')
                    ->orWhere('profissional_id', $agendamento->profissional_id);
            })
            ->orderBy('created_at')
            ->get();

        foreach ($entradas as $entrada) {
            $entrada->forceFill(['avisado_em' => now()])->save();

            Log::info('Horário liberado avisado para a lista de espera', [
                'lista_espera_id' => $entrada->id,
                'agendamento_id' => $agendamento->id,
            ]);
        }
    }
}
