<?php

namespace Database\Seeders;

use App\Models\Agendamento;
use App\Models\Bloqueio;
use App\Models\Disponibilidade;
use App\Models\ListaEspera;
use App\Models\Paciente;
use App\Models\Profissional;
use App\Models\Servico;
use App\Models\Tenant;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class ClinicaBemEstarSeeder extends Seeder
{
    public function run()
    {
        $clinica = Tenant::create([
            'nome' => 'Clínica Bem Estar',
            'slug' => 'bem-estar',
            'timezone' => 'America/Sao_Paulo',
        ]);

        User::create(['tenant_id' => $clinica->id, 'name' => 'Ana Martins', 'email' => 'admin@bem-estar.test', 'password' => 'password', 'papel' => User::PAPEL_ADMIN]);
        User::create(['tenant_id' => $clinica->id, 'name' => 'Lucas Ribeiro', 'email' => 'recepcao@bem-estar.test', 'password' => 'password', 'papel' => User::PAPEL_RECEPCAO]);
        User::create(['tenant_id' => $clinica->id, 'name' => 'Dra. Carla Mendes', 'email' => 'carla@bem-estar.test', 'password' => 'password', 'papel' => User::PAPEL_PROFISSIONAL]);

        $carla = Profissional::create(['tenant_id' => $clinica->id, 'nome' => 'Dra. Carla Mendes', 'especialidade' => 'Clínica geral', 'registro' => 'CRM-SP 123456']);
        $pedro = Profissional::create(['tenant_id' => $clinica->id, 'nome' => 'Dr. Pedro Alves', 'especialidade' => 'Odontologia', 'registro' => 'CRO-SP 98765']);

        foreach ([$carla, $pedro] as $profissional) {
            foreach (range(1, 5) as $dia) {
                Disponibilidade::create(['tenant_id' => $clinica->id, 'profissional_id' => $profissional->id, 'dia_semana' => $dia, 'hora_inicio' => '08:00', 'hora_fim' => '12:00']);
                Disponibilidade::create(['tenant_id' => $clinica->id, 'profissional_id' => $profissional->id, 'dia_semana' => $dia, 'hora_inicio' => '13:00', 'hora_fim' => '18:00']);
            }
        }

        $consulta = Servico::create(['tenant_id' => $clinica->id, 'nome' => 'Consulta', 'duracao_minutos' => 30]);
        $retorno = Servico::create(['tenant_id' => $clinica->id, 'nome' => 'Retorno', 'duracao_minutos' => 20]);
        $limpeza = Servico::create(['tenant_id' => $clinica->id, 'nome' => 'Limpeza', 'duracao_minutos' => 60]);

        $joana = Paciente::create([
            'tenant_id' => $clinica->id,
            'nome' => 'Joana Souza',
            'cpf' => '529.982.247-25',
            'telefone' => '(11) 98765-4321',
            'email' => 'paciente@bem-estar.test',
            'data_nascimento' => '1988-03-14',
            'senha' => 'password',
            'aceita_whatsapp' => true,
        ]);

        $roberto = Paciente::create([
            'tenant_id' => $clinica->id,
            'nome' => 'Roberto Lima',
            'cpf' => '111.444.777-35',
            'telefone' => '(11) 91234-5678',
            'email' => 'roberto@exemplo.test',
            'data_nascimento' => '1975-11-02',
            'aceita_whatsapp' => false,
        ]);

        $marina = Paciente::create([
            'tenant_id' => $clinica->id,
            'nome' => 'Marina Costa',
            'cpf' => '390.533.447-05',
            'telefone' => '(11) 99876-1234',
            'email' => 'marina@exemplo.test',
            'aceita_whatsapp' => true,
        ]);

        $agora = CarbonImmutable::now($clinica->timezone);
        $hoje = $agora->startOfDay();
        $proximaHora = $agora->addHour()->startOfHour();

        $agendar = function (Paciente $paciente, Profissional $profissional, Servico $servico, CarbonImmutable $inicio, string $status = Agendamento::AGENDADO, array $extra = []) use ($clinica) {
            return Agendamento::create(array_merge([
                'tenant_id' => $clinica->id,
                'paciente_id' => $paciente->id,
                'profissional_id' => $profissional->id,
                'servico_id' => $servico->id,
                'inicio' => $inicio,
                'fim' => $inicio->addMinutes($servico->duracao_minutos),
                'status' => $status,
            ], $extra));
        };

        $agendar($marina, $carla, $consulta, $hoje->setTime(9, 0), Agendamento::REALIZADO, ['notas_clinicas' => 'Pressão 12x8. Retorno em 30 dias.']);
        $agendar($roberto, $pedro, $limpeza, $hoje->setTime(10, 0), Agendamento::FALTOU);
        $agendar($joana, $carla, $retorno, $hoje->setTime(11, 0), Agendamento::CONFIRMADO);
        $agendar($marina, $pedro, $consulta, $hoje->setTime(14, 0), Agendamento::CANCELADO);
        $agendar($roberto, $carla, $consulta, $hoje->setTime(16, 30), Agendamento::AGENDADO, ['link_teleconsulta' => 'https://meet.horalis.example/bem-estar-roberto']);

        $agendar($joana, $carla, $consulta, $proximaHora->addHours(2));
        $agendar($roberto, $pedro, $consulta, $proximaHora->addHours(5));
        $agendar($marina, $carla, $retorno, $hoje->addDays(3)->setTime(9, 30));
        $agendar($joana, $pedro, $limpeza, $hoje->subDays(7)->setTime(15, 0), Agendamento::REALIZADO);

        Bloqueio::create(['tenant_id' => $clinica->id, 'profissional_id' => $pedro->id, 'data' => $hoje->addDays(10), 'data_fim' => $hoje->addDays(14), 'motivo' => 'Congresso']);

        ListaEspera::create(['tenant_id' => $clinica->id, 'paciente_id' => $marina->id, 'profissional_id' => $carla->id, 'servico_id' => $consulta->id, 'data_desejada' => $hoje->addDay(), 'observacao' => 'Prefere manhã']);
    }
}
