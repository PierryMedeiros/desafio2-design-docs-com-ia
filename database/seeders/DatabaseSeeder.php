<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Agendamento;
use App\Models\Disponibilidade;
use App\Models\Paciente;
use App\Models\Profissional;
use App\Models\Servico;
use App\Models\Tenant;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $clinicas = [
            ['nome' => 'Clínica Bem Estar', 'slug' => 'bem-estar'],
            ['nome' => 'Fisio Movimento', 'slug' => 'fisio-movimento'],
        ];

        foreach ($clinicas as $dados) {
            $tenant = Tenant::create($dados);

            User::create([
                'tenant_id' => $tenant->id,
                'name' => 'Recepção '.$tenant->nome,
                'email' => 'recepcao@'.$tenant->slug.'.test',
                'password' => Hash::make('password'),
                'papel' => User::PAPEL_RECEPCAO,
            ]);

            $profissional = Profissional::create(['tenant_id' => $tenant->id, 'nome' => 'Dra. Carla Mendes', 'especialidade' => 'Clínica geral']);

            foreach ([1, 2, 3, 4, 5] as $dia) {
                Disponibilidade::create([
                    'tenant_id' => $tenant->id,
                    'profissional_id' => $profissional->id,
                    'dia_semana' => $dia,
                    'hora_inicio' => '08:00',
                    'hora_fim' => '18:00',
                ]);
            }

            $servico = Servico::create(['tenant_id' => $tenant->id, 'nome' => 'Consulta', 'duracao_minutos' => 30]);
            $paciente = Paciente::create([
                'tenant_id' => $tenant->id,
                'nome' => 'Joana Souza',
                'cpf' => '529.982.247-25',
                'telefone' => '11987654321',
                'email' => 'paciente@'.$tenant->slug.'.test',
                'senha' => Hash::make('password'),
            ]);

            foreach (['2022-07-04 09:00', '2022-07-04 10:00', '2022-07-05 14:30'] as $inicio) {
                Agendamento::create([
                    'tenant_id' => $tenant->id,
                    'paciente_id' => $paciente->id,
                    'profissional_id' => $profissional->id,
                    'servico_id' => $servico->id,
                    'inicio' => $inicio,
                    'fim' => date('Y-m-d H:i', strtotime($inicio) + 30 * 60),
                    'status' => Agendamento::AGENDADO,
                ]);
            }
        }

        User::create([
            'tenant_id' => 1,
            'name' => 'Admin',
            'email' => 'admin@bem-estar.test',
            'password' => Hash::make('password'),
            'papel' => User::PAPEL_ADMIN,
        ]);
    }
}
