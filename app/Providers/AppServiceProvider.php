<?php

namespace App\Providers;

use App\Lembretes\CanalLembrete;
use App\Lembretes\FabricaDeCanais;
use App\Tenancy\TenantContext;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);

        $this->app->bind(CanalLembrete::class, fn ($app) => $app->make(FabricaDeCanais::class)->criar());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
