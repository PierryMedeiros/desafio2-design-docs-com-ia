<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Jobs\EnviarLembreteAgendamento;
use App\Models\Agendamento;

class EnfileirarLembretes extends Command
{
    protected $signature = 'lembretes:enfileirar';

    protected $description = 'Enfileira os lembretes dos agendamentos das próximas horas';

    public function handle()
    {
        $total = 0;

        $ids = Agendamento::query()
            ->where('status', Agendamento::AGENDADO)
            ->whereNull('lembrete_enviado_em')
            ->whereRaw("inicio between now() and now() + interval '24 hours'")
            ->orderBy('inicio')
            ->pluck('id');

        foreach ($ids as $id) {
            if (Cache::add("lembretes:enfileirado:{$id}", true, now()->addHour())) {
                EnviarLembreteAgendamento::dispatch($id);
                $total++;
            }
        }

        $this->info("{$total} lembrete(s) enfileirado(s).");
    }
}
