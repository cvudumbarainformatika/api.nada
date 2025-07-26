<?php

use App\Http\Controllers\Api\Simrs\Ranap\Pelayanan\DischargePlanningController;
use App\Http\Controllers\Api\Simrs\Ranap\Pelayanan\PulangController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/ranap/layanan/pulang'
], function () {

  Route::get('/getmasterprognosis', [DischargePlanningController::class, 'getmasterprognosis']);
  Route::get('/getmastercarakeluar', [PulangController::class, 'getmastercarakeluar']);
  Route::post('/simpandata', [PulangController::class, 'simpandata']);


  

});
