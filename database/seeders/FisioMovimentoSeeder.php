<?php

namespace Database\Seeders;

use App\Models\Agendamento;
use App\Models\Disponibilidade;
use App\Models\Paciente;
use App\Models\Profissional;
use App\Models\Servico;
use App\Models\Tenant;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class FisioMovimentoSeeder extends Seeder
{
    public function run()
    {
        $clinica = Tenant::create([
            'nome' => 'Fisio Movimento',
            'slug' => 'fisio-movimento',
            'timezone' => 'America/Sao_Paulo',
        ]);

        User::create(['tenant_id' => $clinica->id, 'name' => 'Paulo Henrique', 'email' => 'recepcao@fisio-movimento.test', 'password' => 'password', 'papel' => User::PAPEL_RECEPCAO]);

        $bruno = Profissional::create(['tenant_id' => $clinica->id, 'nome' => 'Bruno Tavares', 'especialidade' => 'Fisioterapia', 'registro' => 'CREFITO-3 45678-F']);

        foreach (range(1, 6) as $dia) {
            Disponibilidade::create(['tenant_id' => $clinica->id, 'profissional_id' => $bruno->id, 'dia_semana' => $dia, 'hora_inicio' => '07:00', 'hora_fim' => '13:00']);
        }

        $sessao = Servico::create(['tenant_id' => $clinica->id, 'nome' => 'Sessão de fisioterapia', 'duracao_minutos' => 50]);

        $claudia = Paciente::create([
            'tenant_id' => $clinica->id,
            'nome' => 'Cláudia Ferreira',
            'cpf' => '987.654.321-00',
            'telefone' => '(21) 97777-1111',
            'email' => 'claudia@exemplo.test',
            'senha' => 'password',
            'aceita_whatsapp' => true,
        ]);

        $agora = CarbonImmutable::now($clinica->timezone);
        $hoje = $agora->startOfDay();

        foreach ([[$hoje->setTime(8, 0), Agendamento::REALIZADO], [$agora->addHour()->startOfHour()->addHours(3), Agendamento::AGENDADO]] as [$inicio, $status]) {
            Agendamento::create([
                'tenant_id' => $clinica->id,
                'paciente_id' => $claudia->id,
                'profissional_id' => $bruno->id,
                'servico_id' => $sessao->id,
                'inicio' => $inicio,
                'fim' => $inicio->addMinutes($sessao->duracao_minutos),
                'status' => $status,
                'notas_clinicas' => 'Lombalgia, sessão 4 de 10.',
            ]);
        }
    }
}
