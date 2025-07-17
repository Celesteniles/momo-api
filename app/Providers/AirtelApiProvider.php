<?php

namespace App\Providers;

use App\AirtelApi\Src\Objet\AirtelApi;
use Illuminate\Support\ServiceProvider;

class AirtelApiProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton("AirtelApi", function () {
            return new AirtelApi;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
