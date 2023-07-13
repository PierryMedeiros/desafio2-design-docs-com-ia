<?php

namespace App\Providers;

use App\Lembretes\Canais\EmailCanal;
use App\Lembretes\Canais\LogCanal;
use App\Lembretes\Canais\SmsTwilio;
use App\Lembretes\CanalLembrete;
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

        $this->app->bind(CanalLembrete::class, function ($app) {
            if (config('lembretes.canal') !== 'sms') {
                return new EmailCanal;
            }

            $sms = new SmsTwilio(config('lembretes.sms'));

            return config('lembretes.sms.driver') === 'log' ? new LogCanal($sms) : $sms;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
