<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\LembreteConsulta;
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

    public function handle(GerenciadorSchemas $schemas)
    {
        $tenant = Tenant::findOrFail($this->tenantId);
        $schemas->usar($tenant);

        try {
            $agendamento = Agendamento::with(['paciente', 'profissional'])->find($this->agendamentoId);

            if (!$agendamento || $agendamento->lembrete_enviado_em || !$agendamento->paciente->email) {
                return;
            }

            Mail::to($agendamento->paciente->email)->send(new LembreteConsulta($agendamento, $tenant));

            $agendamento->lembrete_enviado_em = now();
            $agendamento->save();
        } finally {
            $schemas->usarPublico();
        }
    }
}
