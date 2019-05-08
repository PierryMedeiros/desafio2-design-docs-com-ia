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
        $tenant = Tenant::findOrFail($request->user()->tenant_id);

        $this->schemas->usar($tenant);

        return $next($request);
    }
}
