<?php

use App\Http\Controllers\MomoApiController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix("momo")->controller(MomoApiController::class)->group(function(){
    Route::post("collection", "collection");
    Route::post("disbursement", "disbursement");
});
