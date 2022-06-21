<?php
namespace Tests\Feature\Painel;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use App\Criptografia\HashCpf;
use App\Models\Paciente;
use Tests\CriaClinica;
use Tests\TestCase;

class PacienteTest extends TestCase
{
    use CriaClinica, RefreshDatabase;

    public function test_cadastra_paciente_com_cpf_criptografado(): void
    {
        $clinica = $this->criarClinica();

        $this->actingAs($this->criarUsuario($clinica))
            ->post('/pacientes', [
                'nome' => 'Helena Rocha',
                'cpf' => '390.533.447-05',
                'telefone' => '(11) 99999-0000',
                'email' => 'helena@exemplo.test',
            ])
            ->assertRedirect();

        $paciente = Paciente::where('nome', 'Helena Rocha')->first();
        $bruto = DB::table('pacientes')->where('id', $paciente->id)->value('cpf');

        $this->assertSame('390.533.447-05', $paciente->cpf);
        $this->assertNotSame('390.533.447-05', $bruto);
        $this->assertSame(HashCpf::gerar('39053344705'), $paciente->cpf_hash);
    }

    public function test_rejeita_cpf_invalido(): void
    {
        $clinica = $this->criarClinica();

        $this->actingAs($this->criarUsuario($clinica))
            ->post('/pacientes', ['nome' => 'Fulano', 'cpf' => '123.456.789-00', 'telefone' => '11999990000'])
            ->assertSessionHasErrors('cpf');
    }

    public function test_busca_por_cpf_usa_o_hash(): void
    {
        $clinica = $this->criarClinica();
        $paciente = $this->criarPaciente($clinica, ['cpf' => '529.982.247-25']);

        $this->actingAs($this->criarUsuario($clinica))
            ->get('/pacientes/busca?cpf=52998224725')
            ->assertRedirect('/pacientes/'.$paciente->id);
    }

    public function test_busca_por_cpf_inexistente_volta_para_a_lista(): void
    {
        $clinica = $this->criarClinica();

        $this->actingAs($this->criarUsuario($clinica))
            ->get('/pacientes/busca?cpf=111.444.777-35')
            ->assertRedirect('/pacientes')
            ->assertSessionHas('aviso');
    }

    public function test_ficha_do_paciente(): void
    {
        $clinica = $this->criarClinica();
        $paciente = $this->criarPaciente($clinica, ['nome' => 'Joana Souza']);

        $this->actingAs($this->criarUsuario($clinica))
            ->get('/pacientes/'.$paciente->id)
            ->assertOk()
            ->assertSee('Joana Souza')
            ->assertSee('529.982.247-25');
    }
}
