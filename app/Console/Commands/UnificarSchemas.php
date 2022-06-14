<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Tenant;

class UnificarSchemas extends Command
{
    protected $signature = 'tenancy:unificar-schemas {--tenant=* : slug das clínicas (padrão: todas)}';

    protected $description = 'Copia os dados dos schemas das clínicas para as tabelas do public com tenant_id (rodar uma vez)';

    private $tabelas = ['profissionais', 'servicos', 'disponibilidades', 'bloqueios', 'pacientes', 'agendamentos', 'anexos'];

    private $chaves = [
        'disponibilidades' => ['profissional_id' => 'profissionais'],
        'bloqueios' => ['profissional_id' => 'profissionais'],
        'agendamentos' => ['paciente_id' => 'pacientes', 'profissional_id' => 'profissionais', 'servico_id' => 'servicos'],
        'anexos' => ['agendamento_id' => 'agendamentos'],
    ];

    public function handle()
    {
        $tenants = Tenant::query()
            ->when($this->option('tenant'), fn ($query, $slugs) => $query->whereIn('slug', $slugs))
            ->orderBy('id')
            ->get();

        $atrasados = $tenants->reject(fn ($tenant) => Schema::hasColumn("{$tenant->schema}.agendamentos", 'convenio'));

        if ($atrasados->isNotEmpty()) {
            $this->error('Schemas sem a coluna convenio (rode o tenants:migrate antes): '.$atrasados->pluck('schema')->implode(', '));

            return 1;
        }

        foreach ($tenants as $tenant) {
            DB::transaction(function () use ($tenant) {
                $mapa = [];

                foreach ($this->tabelas as $tabela) {
                    $mapa[$tabela] = [];
                    $linhas = DB::table("{$tenant->schema}.{$tabela}")->orderBy('id')->get();

                    foreach ($linhas as $linha) {
                        $dados = (array) $linha;
                        $idAntigo = $dados['id'];
                        unset($dados['id']);

                        foreach ($this->chaves[$tabela] ?? [] as $coluna => $origem) {
                            if ($dados[$coluna] !== null) {
                                $dados[$coluna] = $mapa[$origem][$dados[$coluna]];
                            }
                        }

                        $dados['tenant_id'] = $tenant->id;
                        $mapa[$tabela][$idAntigo] = DB::table("public.{$tabela}")->insertGetId($dados);
                    }

                    $this->line("{$tenant->schema}.{$tabela}: ".count($linhas));
                }
            });

            $this->info("{$tenant->nome} copiada.");
        }

        $this->warn('Os tokens do app não são copiados: os pacientes vão precisar entrar de novo.');

        return 0;
    }
}
