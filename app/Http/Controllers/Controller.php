<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function tenant(): Tenant
    {
        return app(TenantContext::class)->tenant();
    }
}
