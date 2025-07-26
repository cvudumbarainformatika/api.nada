<?php

use App\Http\Controllers\Api\Simrs\Ranap\Pelayanan\DischargePlanningController;
use App\Http\Controllers\Api\Simrs\Ranap\Pelayanan\NursenoteController;
use App\Http\Controllers\Api\Simrs\Ranap\Pelayanan\PulangController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/ranap/layanan/nursenote'
], function () {

  Route::get('/list', [NursenoteController::class, 'list']);
  Route::post('/simpan', [NursenoteController::class, 'simpan']);
  Route::post('/hapus', [NursenoteController::class, 'delete']);
});
