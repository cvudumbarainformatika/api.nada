<?php

use App\Http\Controllers\Api\Simrs\Ranap\Pelayanan\TargetController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/ranap/layanan/target'
], function () {
    // Route::post('/simpan', function (Request $request) {
    //     // return 'ok';

    //     $data=[
    //       'html'=> $request->target,
    //       'text'=> htmlspecialchars(trim(strip_tags($request->target)))
    //     ];

    //     return new JsonResponse($data);
    // });
    Route::post('/simpan', [TargetController::class, 'simpan']);
    
});
