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
use App\Tenancy\GerenciadorSchemas;

class DatabaseSeeder extends Seeder
{
    public function run(GerenciadorSchemas $schemas)
    {
        $clinicas = [
            ['nome' => 'Clínica Bem Estar', 'slug' => 'bem-estar', 'schema' => 'clinica_bem_estar'],
            ['nome' => 'Fisio Movimento', 'slug' => 'fisio-movimento', 'schema' => 'clinica_fisio_movimento'],
        ];

        foreach ($clinicas as $dados) {
            $tenant = Tenant::create($dados);
            $schemas->criar($tenant);

            User::create([
                'tenant_id' => $tenant->id,
                'name' => 'Recepção '.$tenant->nome,
                'email' => 'recepcao@'.$tenant->slug.'.test',
                'password' => Hash::make('password'),
                'papel' => User::PAPEL_RECEPCAO,
            ]);

            $schemas->usar($tenant);

            $profissional = Profissional::create(['nome' => 'Dra. Carla Mendes', 'especialidade' => 'Clínica geral']);

            foreach ([1, 2, 3, 4, 5] as $dia) {
                Disponibilidade::create([
                    'profissional_id' => $profissional->id,
                    'dia_semana' => $dia,
                    'hora_inicio' => '08:00',
                    'hora_fim' => '18:00',
                ]);
            }

            $servico = Servico::create(['nome' => 'Consulta', 'duracao_minutos' => 30]);
            $paciente = Paciente::create(['nome' => 'Joana Souza', 'telefone' => '11987654321', 'email' => 'joana@exemplo.test']);

            foreach (['2019-06-24 09:00', '2019-06-24 10:00', '2019-06-25 14:30'] as $inicio) {
                Agendamento::create([
                    'paciente_id' => $paciente->id,
                    'profissional_id' => $profissional->id,
                    'servico_id' => $servico->id,
                    'inicio' => $inicio,
                    'fim' => date('Y-m-d H:i', strtotime($inicio) + 30 * 60),
                    'status' => Agendamento::AGENDADO,
                ]);
            }

            $schemas->usarPublico();
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
