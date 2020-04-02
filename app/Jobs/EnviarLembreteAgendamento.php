<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Lembretes\CanalLembrete;
use App\Models\Agendamento;
use App\Models\Tenant;
use App\Tenancy\GerenciadorSchemas;

class EnviarLembreteAgendamento implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;

    public $timeout = 120;

    public $tenantId;

    public $agendamentoId;

    public function __construct($tenantId, $agendamentoId)
    {
        $this->tenantId = $tenantId;
        $this->agendamentoId = $agendamentoId;
        $this->onQueue('notificacoes');
    }

    public function handle(GerenciadorSchemas $schemas, CanalLembrete $canal)
    {
        $tenant = Tenant::findOrFail($this->tenantId);
        $schemas->usar($tenant);

        try {
            $agendamento = Agendamento::with(['paciente', 'profissional'])->find($this->agendamentoId);

            if (!$agendamento || $agendamento->lembrete_enviado_em) {
                return;
            }

            $canal->enviar($agendamento->paciente, $this->mensagem($agendamento, $tenant));

            $agendamento->lembrete_enviado_em = now();
            $agendamento->save();
        } finally {
            $schemas->usarPublico();
        }
    }

    private function mensagem(Agendamento $agendamento, Tenant $tenant)
    {
        $mensagem = sprintf(
            'Olá, %s! Lembrete da sua consulta na %s em %s às %s com %s.',
            strtok($agendamento->paciente->nome, ' '),
            $tenant->nome,
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
