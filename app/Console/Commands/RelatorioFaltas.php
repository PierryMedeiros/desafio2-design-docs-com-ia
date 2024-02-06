<?php

namespace App\Console\Commands;

use App\Models\Agendamento;
use App\Models\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class RelatorioFaltas extends Command
{
    protected $signature = 'relatorios:faltas {clinica : slug da clínica} {--mes= : mês no formato AAAA-MM}';

    protected $description = 'Taxa de faltas da clínica no mês';

    public function handle(): int
    {
        $tenant = Tenant::where('slug', $this->argument('clinica'))->first();

        if (! $tenant) {
            $this->error('Clínica não encontrada.');

            return self::FAILURE;
        }

        $mes = $this->option('mes')
            ? CarbonImmutable::createFromFormat('Y-m-d', $this->option('mes').'-01')
            : CarbonImmutable::now($tenant->timezone)->subMonth();

        $agendamentos = Agendamento::query()
            ->where('tenant_id', $tenant->id)
            ->whereBetween('inicio', [
                $mes->startOfMonth()->toDateTimeString(),
                $mes->endOfMonth()->toDateTimeString(),
            ]);

        $total = (clone $agendamentos)->count();
        $faltas = (clone $agendamentos)->where('status', Agendamento::FALTOU)->count();

        $this->table(
            ['Clínica', 'Agendamentos', 'Faltas', 'Taxa'],
            [[
                $tenant->nome,
                $total,
                $faltas,
                number_format($faltas / max($total, 1) * 100, 1, ',', '.').'%',
            ]]
        );

        return self::SUCCESS;
    }
}
