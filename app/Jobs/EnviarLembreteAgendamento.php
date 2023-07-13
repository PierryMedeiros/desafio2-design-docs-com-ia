<?php

namespace App\Jobs;

use App\Lembretes\CanalLembrete;
use App\Models\Agendamento;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EnviarLembreteAgendamento implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;

    public $timeout = 120;

    public function __construct(public int $agendamentoId)
    {
        $this->onQueue('notificacoes');
    }

    public function handle(CanalLembrete $canal): void
    {
        $agendamento = Agendamento::with(['paciente', 'profissional', 'servico', 'tenant'])->find($this->agendamentoId);

        if (! $agendamento || $agendamento->status !== Agendamento::AGENDADO || $agendamento->lembrete_enviado_em) {
            return;
        }

        $canal->enviar($agendamento->paciente, $this->mensagem($agendamento));

        $agendamento->forceFill(['lembrete_enviado_em' => now()])->save();
    }

    private function mensagem(Agendamento $agendamento): string
    {
        $mensagem = sprintf(
            'Olá, %s! Lembrete da sua consulta na %s em %s às %s com %s.',
            strtok($agendamento->paciente->nome, ' '),
            $agendamento->tenant->nome,
            $agendamento->inicio->format('d/m'),
            $agendamento->inicio->format('H:i'),
            $agendamento->profissional->nome
        );

        if ($agendamento->link_teleconsulta) {
            $mensagem .= ' Link da teleconsulta: '.$agendamento->link_teleconsulta;
        }

        return $mensagem;
    }
}
