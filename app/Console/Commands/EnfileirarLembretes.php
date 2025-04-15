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

    public function handle(): int
    {
        $total = 0;

        Tenant::query()->orderBy('id')->each(function (Tenant $tenant) use (&$total) {
            $agora = $tenant->agora();
            $limite = $agora->addHours(config('lembretes.antecedencia_horas'));

            Agendamento::query()
                ->where('tenant_id', $tenant->id)
                ->whereIn('status', [Agendamento::AGENDADO, Agendamento::CONFIRMADO])
                ->whereNull('lembrete_enviado_em')
                ->whereBetween('inicio', [$agora->toDateTimeString(), $limite->toDateTimeString()])
                ->orderBy('inicio')
                ->pluck('id')
                ->each(function (int $id) use (&$total) {
                    if (Cache::add("lembretes:enfileirado:{$id}", true, now()->addHour())) {
                        EnviarLembreteAgendamento::dispatch($id);
                        $total++;
                    }
                });
        });

        $this->info("{$total} lembrete(s) enfileirado(s).");

        return self::SUCCESS;
    }
}
