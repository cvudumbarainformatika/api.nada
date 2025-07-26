<?php

use App\Http\Controllers\Api\Simrs\Kasir\BankjatiminsertController;
use Illuminate\Support\Facades\Route;


Route::group([
    // 'middleware' => 'block.ip',
    // 'prefix' => 'simrs/kasir'
    // 'prefix' => 'bridbangjatim'
], function () {
    Route::post('/simrs/kasir/PaymentVirtual/insert', [BankjatiminsertController::class, 'insertqrisbayar']);
});
