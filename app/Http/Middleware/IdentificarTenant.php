<?php
namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Closure;
use App\Models\Tenant;
use App\Tenancy\TenantContext;

class IdentificarTenant
{
    public function __construct(private TenantContext $contexto)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        abort_if(!$usuario, 401);

        $tenant = Tenant::find($usuario->tenant_id);

        abort_if(!$tenant, 403);

        $this->contexto->definir($tenant);

        return $next($request);
    }
}
