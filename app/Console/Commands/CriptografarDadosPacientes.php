<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use App\Criptografia\HashCpf;
use App\Models\Tenant;
use App\Tenancy\GerenciadorSchemas;

class CriptografarDadosPacientes extends Command
{
    protected $signature = 'pacientes:criptografar';

    protected $description = 'Criptografa CPF e notas clínicas gravados antes da criptografia por campo';

    public function handle(GerenciadorSchemas $schemas)
    {
        $pacientes = 0;
        $agendamentos = 0;

        foreach (Tenant::orderBy('id')->get() as $tenant) {
            $schemas->usar($tenant);

            DB::table('pacientes')->orderBy('id')->each(function ($paciente) use (&$pacientes) {
                if ($paciente->cpf === null || $this->jaCriptografado($paciente->cpf)) {
                    return;
                }

                DB::table('pacientes')->where('id', $paciente->id)->update([
                    'cpf' => Crypt::encryptString($paciente->cpf),
                    'cpf_hash' => HashCpf::gerar($paciente->cpf),
                ]);

                $pacientes++;
            });

            DB::table('agendamentos')->whereNotNull('notas_clinicas')->orderBy('id')->each(function ($agendamento) use (&$agendamentos) {
                if ($this->jaCriptografado($agendamento->notas_clinicas)) {
                    return;
                }

                DB::table('agendamentos')->where('id', $agendamento->id)->update([
                    'notas_clinicas' => Crypt::encryptString($agendamento->notas_clinicas),
                ]);

                $agendamentos++;
            });
        }

        $schemas->usarPublico();

        $this->info("Pacientes: {$pacientes}. Agendamentos: {$agendamentos}.");

        return 0;
    }

    private function jaCriptografado($valor)
    {
        try {
            Crypt::decryptString($valor);

            return true;
        } catch (DecryptException $e) {
            return false;
        }
    }
}
