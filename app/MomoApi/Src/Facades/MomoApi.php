<?php

namespace App\MomoApi\Src\Facades;

use Illuminate\Support\Facades\Facade;

class MomoApi extends Facade
{
    protected static function getFacadeAccessor()
    {
        return "MomoApi";
    }
}
