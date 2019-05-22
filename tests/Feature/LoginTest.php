<?php
namespace Tests\Feature;

use Tests\TestCase;

class LoginTest extends TestCase
{
    public function testTelaDeLoginAbre()
    {
        $this->get('/login')->assertStatus(200)->assertSee('Entrar');
    }

    public function testAgendaExigeLogin()
    {
        $this->get('/agenda')->assertRedirect('/login');
    }
}
