<?php
namespace Tests\Feature\Painel;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CriaClinica;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use CriaClinica, RefreshDatabase;

    public function test_tela_de_login_abre(): void
    {
        $this->get('/login')->assertOk()->assertSee('Entrar');
    }

    public function test_login_com_credenciais_validas_vai_para_a_agenda(): void
    {
        $usuario = $this->criarUsuario($this->criarClinica());

        $this->post('/login', ['email' => $usuario->email, 'password' => 'password'])
            ->assertRedirect('/agenda');

        $this->assertAuthenticatedAs($usuario);
    }

    public function test_login_com_senha_errada_volta_com_erro(): void
    {
        $usuario = $this->criarUsuario($this->criarClinica());

        $this->from('/login')
            ->post('/login', ['email' => $usuario->email, 'password' => 'errada'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_agenda_exige_login(): void
    {
        $this->get('/agenda')->assertRedirect('/login');
    }
}
