<?php

namespace App\Tenancy;

use App\Models\Tenant;

class TenantContext
{
    private ?Tenant $tenant = null;

    public function definir(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function limpar(): void
    {
        $this->tenant = null;
    }

    public function tenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function id(): ?int
    {
        return $this->tenant?->id;
    }

    public function ativo(): bool
    {
        return $this->tenant !== null;
    }
}
