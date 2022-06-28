<?php
namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Closure;
use App\Models\Tenant;
use App\Tenancy\TenantContext;

class IdentificarTenantPorCabecalho
{
    public function __construct(private TenantContext $contexto)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $tenant = Tenant::where('slug', $request->header('X-Clinica'))->firstOrFail();

        $this->contexto->definir($tenant);

        return $next($request);
    }
}
