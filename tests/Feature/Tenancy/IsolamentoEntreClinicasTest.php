<?php

namespace Tests\Feature\Tenancy;

use App\Models\Anexo;
use App\Models\Paciente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\CriaClinica;
use Tests\TestCase;

class IsolamentoEntreClinicasTest extends TestCase
{
    use CriaClinica, RefreshDatabase;

    public function test_usuario_nao_ve_paciente_de_outra_clinica(): void
    {
        $bemEstar = $this->criarClinica('bem-estar');
        $fisio = $this->criarClinica('fisio');
        $pacienteDaFisio = $this->criarPaciente($fisio, ['nome' => 'Paciente da Fisio']);

        $usuario = $this->criarUsuario($bemEstar);

        $this->actingAs($usuario)
            ->get('/pacientes/'.$pacienteDaFisio->id)
            ->assertNotFound();

        $this->actingAs($usuario)
            ->get('/pacientes')
            ->assertOk()
            ->assertDontSee('Paciente da Fisio');
    }

    public function test_agenda_mostra_so_a_clinica_do_usuario(): void
    {
        $bemEstar = $this->criarClinica('bem-estar');
        $fisio = $this->criarClinica('fisio');
        $dia = $this->proximoDiaUtil();

        $this->criarAgendamento($bemEstar, $this->criarPaciente($bemEstar, ['nome' => 'Paciente da Bem Estar']), $this->criarProfissional($bemEstar), $this->criarServico($bemEstar), $dia->setTime(9, 0));
        $this->criarAgendamento($fisio, $this->criarPaciente($fisio, ['nome' => 'Paciente da Fisio']), $this->criarProfissional($fisio), $this->criarServico($fisio), $dia->setTime(9, 0));

        $this->actingAs($this->criarUsuario($bemEstar))
            ->get('/agenda?data='.$dia->toDateString())
            ->assertOk()
            ->assertSee('Paciente da Bem Estar')
            ->assertDontSee('Paciente da Fisio');
    }

    public function test_busca_por_cpf_nao_encontra_paciente_de_outra_clinica(): void
    {
        $bemEstar = $this->criarClinica('bem-estar');
        $fisio = $this->criarClinica('fisio');
        $this->criarPaciente($fisio, ['cpf' => '987.654.321-00']);

        $this->actingAs($this->criarUsuario($bemEstar))
            ->get('/pacientes/busca?cpf=98765432100')
            ->assertRedirect('/pacientes');
    }

    public function test_usuario_nao_altera_nem_baixa_anexo_de_outra_clinica(): void
    {
        $bemEstar = $this->criarClinica('bem-estar');
        $fisio = $this->criarClinica('fisio');
        $agendamento = $this->criarAgendamento($fisio, $this->criarPaciente($fisio), $this->criarProfissional($fisio), $this->criarServico($fisio), $this->proximoDiaUtil()->setTime(9, 0));
        $anexo = Anexo::create(['tenant_id' => $fisio->id, 'agendamento_id' => $agendamento->id, 'caminho' => 'x.pdf', 'nome_original' => 'x.pdf']);
        $usuario = $this->criarUsuario($bemEstar);

        $this->actingAs($usuario)->patch("/agendamentos/{$agendamento->id}/status", ['status' => 'cancelado'])->assertNotFound();
        $this->actingAs($usuario)->get("/anexos/{$anexo->id}")->assertNotFound();

        $this->assertSame('agendado', $agendamento->fresh()->status);
    }

    public function test_paciente_do_app_nao_cancela_agendamento_de_outra_clinica(): void
    {
        $bemEstar = $this->criarClinica('bem-estar');
        $fisio = $this->criarClinica('fisio');
        $agendamento = $this->criarAgendamento($fisio, $this->criarPaciente($fisio), $this->criarProfissional($fisio), $this->criarServico($fisio), $this->proximoDiaUtil()->setTime(9, 0));

        Sanctum::actingAs($this->criarPaciente($bemEstar));

        $this->deleteJson("/api/v1/agendamentos/{$agendamento->id}")->assertNotFound();

        $this->assertSame('agendado', $agendamento->fresh()->status);
    }

    public function test_paciente_criado_herda_a_clinica_do_contexto(): void
    {
        $bemEstar = $this->criarClinica('bem-estar');

        $this->actingAs($this->criarUsuario($bemEstar))
            ->post('/pacientes', ['nome' => 'Novo Paciente', 'cpf' => '111.444.777-35', 'telefone' => '11999990000'])
            ->assertRedirect();

        $this->assertSame($bemEstar->id, Paciente::withoutGlobalScopes()->where('nome', 'Novo Paciente')->value('tenant_id'));
    }
}
