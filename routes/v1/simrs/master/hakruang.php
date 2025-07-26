<?php

use App\Http\Controllers\Api\Simrs\Master\AsalrujukanContoller;
use App\Models\Simrs\Ranap\Mruangranap;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/master'
], function () {
    Route::get('/hakruang', function () {
        $data = Mruangranap::select('rs1','rs2','rs3')
            ->where('rs6','<>','1')
            ->where('status','<>','1')
            ->distinct()
            ->orderBy('rs1', 'ASC')
            ->get();
        return response()->json($data);
    });
});
