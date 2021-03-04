<?php
namespace App\Http\Middleware;

use Closure;
use App\Models\Tenant;
use App\Tenancy\GerenciadorSchemas;

class DefinirSchemaTenant
{
    private $schemas;

    public function __construct(GerenciadorSchemas $schemas)
    {
        $this->schemas = $schemas;
    }

    public function handle($request, Closure $next)
    {
        $tenant = $request->user()
            ? Tenant::findOrFail($request->user()->tenant_id)
            : Tenant::where('slug', $request->header('X-Clinica'))->firstOrFail();

        $this->schemas->usar($tenant);

        return $next($request);
    }
}
