<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use GuzzleHttp\Client;
use App\Lembretes\Canais\EmailCanal;
use App\Lembretes\Canais\SmsTwilio;
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

        $this->app->bind(CanalLembrete::class, function ($app) {
            if (config('lembretes.canal') === 'sms') {
                return new SmsTwilio(new Client(), config('lembretes.sms'));
            }

            return new EmailCanal();
        });
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
