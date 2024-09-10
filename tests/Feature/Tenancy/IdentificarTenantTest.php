<?php

namespace Tests\Feature\Tenancy;

use App\Http\Middleware\IdentificarTenant;
use App\Models\Paciente;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\CriaClinica;
use Tests\TestCase;

class IdentificarTenantTest extends TestCase
{
    use CriaClinica, RefreshDatabase;

    public function test_define_a_clinica_do_usuario_logado(): void
    {
        $clinica = $this->criarClinica();
        $usuario = $this->criarUsuario($clinica);
        $contexto = new TenantContext;

        $request = Request::create('/agenda');
        $request->setUserResolver(fn () => $usuario);

        (new IdentificarTenant($contexto))->handle($request, fn () => response('ok'));

        $this->assertSame($clinica->id, $contexto->id());
    }

    public function test_define_a_clinica_do_paciente_dono_do_token(): void
    {
        $clinica = $this->criarClinica();
        $paciente = $this->criarPaciente($clinica);
        $contexto = new TenantContext;

        $request = Request::create('/api/v1/agendamentos');
        $request->setUserResolver(fn () => $paciente);

        (new IdentificarTenant($contexto))->handle($request, fn () => response('ok'));

        $this->assertSame($clinica->id, $contexto->id());
    }

    public function test_sem_usuario_responde_401(): void
    {
        $this->expectException(HttpException::class);

        (new IdentificarTenant(new TenantContext))->handle(Request::create('/agenda'), fn () => response('ok'));
    }

    public function test_escopo_global_filtra_pela_clinica_do_contexto(): void
    {
        $bemEstar = $this->criarClinica('bem-estar');
        $fisio = $this->criarClinica('fisio');
        $this->criarPaciente($bemEstar);
        $this->criarPaciente($fisio);

        $this->assertSame(2, Paciente::count());

        app(TenantContext::class)->definir($fisio);

        $this->assertSame(1, Paciente::count());
        $this->assertSame($fisio->id, Paciente::first()->tenant_id);
    }
}
