<?php

namespace App\Providers;

use App\MomoApi\Src\Objet\MomoApi;
use Illuminate\Support\ServiceProvider;

class MomoApiProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton("MomoApi",function(){
            return new MomoApi;
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
