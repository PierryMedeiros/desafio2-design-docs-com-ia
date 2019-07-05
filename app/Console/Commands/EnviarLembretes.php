<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\LembreteConsulta;
use App\Models\Agendamento;
use App\Models\Tenant;
use App\Tenancy\GerenciadorSchemas;

class EnviarLembretes extends Command
{
    protected $signature = 'lembretes:enviar';

    protected $description = 'Envia por e-mail o lembrete das consultas das próximas 24 horas';

    public function handle(GerenciadorSchemas $schemas)
    {
        $enviados = 0;

        foreach (Tenant::orderBy('id')->get() as $tenant) {
            $schemas->usar($tenant);

            $agendamentos = Agendamento::with(['paciente', 'profissional'])
                ->whereNull('lembrete_enviado_em')
            ->where('status', Agendamento::AGENDADO)
                ->whereBetween('inicio', [now(), now()->addDay()])
                ->get();

            foreach ($agendamentos as $agendamento) {
                if (!$agendamento->paciente->email) {
                    continue;
                }

                Mail::to($agendamento->paciente->email)->send(new LembreteConsulta($agendamento, $tenant));

                $agendamento->lembrete_enviado_em = now();
                $agendamento->save();
                $enviados++;
            }
        }

        $schemas->usarPublico();

        $this->info("{$enviados} lembrete(s) enviado(s).");
    }
}
