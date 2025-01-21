<?php

namespace App\Console\Commands;

use App\Models\Agendamento;
use App\Models\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RelatorioFaltas extends Command
{
    protected $signature = 'relatorios:faltas {clinica : slug da clínica} {--mes= : mês no formato AAAA-MM}';

    protected $description = 'Taxa de faltas por profissional no mês';

    public function handle(): int
    {
        $tenant = Tenant::where('slug', $this->argument('clinica'))->first();

        if (! $tenant) {
            $this->error('Clínica não encontrada.');

            return self::FAILURE;
        }

        $mes = $this->option('mes')
            ? CarbonImmutable::createFromFormat('Y-m-d', $this->option('mes').'-01')
            : $tenant->agora()->subMonth();

        $linhas = Agendamento::query()
            ->join('profissionais', 'profissionais.id', '=', 'agendamentos.profissional_id')
            ->where('agendamentos.tenant_id', $tenant->id)
            ->whereIn('agendamentos.status', [Agendamento::REALIZADO, Agendamento::FALTOU])
            ->whereBetween('agendamentos.inicio', [
                $mes->startOfMonth()->toDateTimeString(),
                $mes->endOfMonth()->toDateTimeString(),
            ])
            ->groupBy('profissionais.nome')
            ->orderBy('profissionais.nome')
            ->get([
                'profissionais.nome',
                DB::raw('count(*) as total'),
                DB::raw("sum(case when agendamentos.status = 'faltou' then 1 else 0 end) as faltas"),
            ]);

        $this->table(
            ['Profissional', 'Atendimentos', 'Faltas', 'Taxa'],
            $linhas->map(fn ($linha) => [
                $linha->nome,
                $linha->total,
                $linha->faltas,
                number_format($linha->faltas / max($linha->total, 1) * 100, 1, ',', '.').'%',
            ])
        );

        return self::SUCCESS;
    }
}
