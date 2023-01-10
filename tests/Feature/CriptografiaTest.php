<?php

namespace Tests\Feature;

use App\Criptografia\HashCpf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\CriaClinica;
use Tests\TestCase;

class CriptografiaTest extends TestCase
{
    use CriaClinica, RefreshDatabase;

    public function test_notas_clinicas_ficam_criptografadas_no_banco(): void
    {
        $clinica = $this->criarClinica();
        $agendamento = $this->criarAgendamento($clinica, $this->criarPaciente($clinica), $this->criarProfissional($clinica), $this->criarServico($clinica), $this->proximoDiaUtil()->setTime(9, 0));

        $agendamento->update(['notas_clinicas' => 'Paciente relata dor lombar']);

        $bruto = DB::table('agendamentos')->where('id', $agendamento->id)->value('notas_clinicas');

        $this->assertStringNotContainsString('lombar', $bruto);
        $this->assertSame('Paciente relata dor lombar', $agendamento->fresh()->notas_clinicas);
    }

    public function test_comando_criptografa_dados_antigos(): void
    {
        $clinica = $this->criarClinica();
        $paciente = $this->criarPaciente($clinica);

        DB::table('pacientes')->where('id', $paciente->id)->update(['cpf' => '529.982.247-25', 'cpf_hash' => null]);

        $this->artisan('pacientes:criptografar')->assertSuccessful();

        $bruto = DB::table('pacientes')->where('id', $paciente->id)->first();

        $this->assertSame('529.982.247-25', Crypt::decryptString($bruto->cpf));
        $this->assertSame(HashCpf::gerar('52998224725'), $bruto->cpf_hash);
    }
}
