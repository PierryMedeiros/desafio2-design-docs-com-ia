<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Lembretes\Canais\EmailCanal;
use App\Lembretes\CanalLembrete;
use App\Tenancy\GerenciadorSchemas;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(GerenciadorSchemas::class);
        $this->app->bind(CanalLembrete::class, EmailCanal::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
