<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Jobs\EnviarLembreteAgendamento;
use App\Models\Agendamento;
use App\Models\Tenant;
use App\Tenancy\GerenciadorSchemas;

class EnfileirarLembretes extends Command
{
    protected $signature = 'lembretes:enfileirar';

    protected $description = 'Enfileira os lembretes dos agendamentos das próximas 24 horas';

    public function handle(GerenciadorSchemas $schemas)
    {
        $total = 0;

        foreach (Tenant::orderBy('id')->get() as $tenant) {
            $schemas->usar($tenant);

            $ids = Agendamento::whereNull('lembrete_enviado_em')
                ->where('status', Agendamento::AGENDADO)
                ->whereBetween('inicio', [now(), now()->addDay()])
                ->pluck('id');

            foreach ($ids as $id) {
                if (Cache::add("lembretes:enfileirado:{$tenant->id}:{$id}", true, now()->addHour())) {
                    EnviarLembreteAgendamento::dispatch($tenant->id, $id);
                    $total++;
                }
            }
        }

        $schemas->usarPublico();

        $this->info("{$total} lembrete(s) enfileirado(s).");
    }
}
