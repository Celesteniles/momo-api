<?php

namespace App\AirtelApi\Src\Facades;

use Illuminate\Support\Facades\Facade;

class AirtelApi extends Facade
{
    protected static function getFacadeAccessor()
    {
        return "AirtelApi";
    }
}
