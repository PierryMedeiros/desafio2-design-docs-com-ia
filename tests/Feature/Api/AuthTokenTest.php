<?php
namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CriaClinica;
use Tests\TestCase;

class AuthTokenTest extends TestCase
{
    use CriaClinica, RefreshDatabase;

    public function test_emite_token_para_o_paciente(): void
    {
        $clinica = $this->criarClinica('bem-estar');
        $paciente = $this->criarPaciente($clinica);

        $this->postJson('/api/v1/auth/token', [
            'email' => $paciente->email,
            'senha' => 'password',
            'clinica' => 'bem-estar',
        ])
            ->assertCreated()
            ->assertJsonStructure(['token', 'tipo', 'paciente' => ['id', 'nome']]);
    }

    public function test_senha_errada_ou_clinica_errada_respondem_422(): void
    {
        $clinica = $this->criarClinica('bem-estar');
        $this->criarClinica('fisio');
        $paciente = $this->criarPaciente($clinica);

        $this->postJson('/api/v1/auth/token', ['email' => $paciente->email, 'senha' => 'errada', 'clinica' => 'bem-estar'])
            ->assertStatus(422);

        $this->postJson('/api/v1/auth/token', ['email' => $paciente->email, 'senha' => 'password', 'clinica' => 'fisio'])
            ->assertStatus(422);
    }

    public function test_rotas_da_api_exigem_token(): void
    {
        $this->getJson('/api/v1/agendamentos')->assertUnauthorized();
    }
}
