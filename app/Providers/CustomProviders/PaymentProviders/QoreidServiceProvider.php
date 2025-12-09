<?php

namespace App\Providers\CustomProviders\PaymentProviders;

use App\Modules\Transaction\Services\External\QoreidService;
use Illuminate\Support\ServiceProvider;


class QoreidServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(QoreidService::class, function () {
            return new QoreidService(self::resolveBaseurl());
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }


    /**
     * Resolve the base URL for the Qoreid API from the configuration.
     *
     * @return string The base URL for the Qoreid API.
     */
    public static function resolveBaseurl(): string
    {
        return config('services.Qoreid.base_url');
    }
}
