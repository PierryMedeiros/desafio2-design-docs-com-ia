<?php

namespace Tests;

use App\Models\Agendamento;
use App\Models\Disponibilidade;
use App\Models\Paciente;
use App\Models\Profissional;
use App\Models\Servico;
use App\Models\Tenant;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Hash;

trait CriaClinica
{
    protected function criarClinica(string $slug = 'clinica-teste'): Tenant
    {
        return Tenant::create([
            'nome' => 'Clínica '.$slug,
            'slug' => $slug,
            'timezone' => 'America/Sao_Paulo',
        ]);
    }

    protected function criarUsuario(Tenant $clinica, string $papel = User::PAPEL_RECEPCAO): User
    {
        return User::create([
            'tenant_id' => $clinica->id,
            'name' => 'Usuário '.$clinica->slug,
            'email' => $papel.'@'.$clinica->slug.'.test',
            'password' => Hash::make('password'),
            'papel' => $papel,
        ]);
    }

    protected function criarProfissional(Tenant $clinica, array $dias = [1, 2, 3, 4, 5, 6, 0]): Profissional
    {
        $profissional = Profissional::create([
            'tenant_id' => $clinica->id,
            'nome' => 'Dra. Teste',
            'especialidade' => 'Clínica geral',
        ]);

        foreach ($dias as $dia) {
            Disponibilidade::create([
                'tenant_id' => $clinica->id,
                'profissional_id' => $profissional->id,
                'dia_semana' => $dia,
                'hora_inicio' => '08:00',
                'hora_fim' => '12:00',
            ]);
        }

        return $profissional;
    }

    protected function criarServico(Tenant $clinica, int $duracao = 30): Servico
    {
        return Servico::create([
            'tenant_id' => $clinica->id,
            'nome' => 'Consulta',
            'duracao_minutos' => $duracao,
        ]);
    }

    protected function criarPaciente(Tenant $clinica, array $dados = []): Paciente
    {
        return Paciente::create(array_merge([
            'tenant_id' => $clinica->id,
            'nome' => 'Paciente '.$clinica->slug,
            'cpf' => '529.982.247-25',
            'telefone' => '(11) 98765-4321',
            'email' => 'paciente@'.$clinica->slug.'.test',
            'senha' => Hash::make('password'),
        ], $dados));
    }

    protected function criarAgendamento(Tenant $clinica, Paciente $paciente, Profissional $profissional, Servico $servico, CarbonImmutable $inicio, string $status = Agendamento::AGENDADO): Agendamento
    {
        return Agendamento::create([
            'tenant_id' => $clinica->id,
            'paciente_id' => $paciente->id,
            'profissional_id' => $profissional->id,
            'servico_id' => $servico->id,
            'inicio' => $inicio,
            'fim' => $inicio->addMinutes($servico->duracao_minutos),
            'status' => $status,
        ]);
    }

    protected function proximoDiaUtil(): CarbonImmutable
    {
        $dia = CarbonImmutable::now('America/Sao_Paulo')->addDay()->startOfDay();

        while (in_array($dia->format('m-d'), config('feriados.fixos'), true)) {
            $dia = $dia->addDay();
        }

        return $dia;
    }
}
