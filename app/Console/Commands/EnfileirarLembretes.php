<?php

namespace App\Console\Commands;

use App\Jobs\EnviarLembreteAgendamento;
use App\Models\Agendamento;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class EnfileirarLembretes extends Command
{
    protected $signature = 'lembretes:enfileirar';

    protected $description = 'Enfileira os lembretes dos agendamentos das próximas horas';

    public function handle()
    {
        $total = 0;

        foreach (Tenant::orderBy('id')->get() as $tenant) {
            $agora = now($tenant->timezone);
            $limite = $agora->copy()->addHours(config('lembretes.antecedencia_horas'));

            $ids = Agendamento::query()
                ->where('tenant_id', $tenant->id)
                ->where('status', Agendamento::AGENDADO)
                ->whereNull('lembrete_enviado_em')
                ->whereBetween('inicio', [$agora->toDateTimeString(), $limite->toDateTimeString()])
                ->orderBy('inicio')
                ->pluck('id');

            foreach ($ids as $id) {
                if (Cache::add("lembretes:enfileirado:{$id}", true, now()->addHour())) {
                    EnviarLembreteAgendamento::dispatch($id);
                    $total++;
                }
            }
        }

        $this->info("{$total} lembrete(s) enfileirado(s).");
    }
}
