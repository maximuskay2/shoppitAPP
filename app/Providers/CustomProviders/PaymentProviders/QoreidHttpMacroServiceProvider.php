<?php

namespace App\Providers\CustomProviders\PaymentProviders;

use App\Http\QoreidHttpMacro;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class QoreidHttpMacroServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        Http::macro('talkToQoreid', function (string $url, string $method = 'GET', array $data = []) {
            return QoreidHttpMacro::makeApiCall($url, QoreidServiceProvider::resolveBaseurl(), $method, $data);
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
}
