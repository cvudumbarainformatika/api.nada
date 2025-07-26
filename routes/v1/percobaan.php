<?php

use App\Events\ChatMessageEvent;
use App\Http\Controllers\Api\PerusahaanController;
use App\Websockets\SocketHandler\UpdatePostSocketHandler;
use BeyondCode\LaravelWebSockets\Facades\WebSocketsRouter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tymon\JWTAuth\JWTAuth;


Route::middleware('auth:api')
    ->group(function () {
        Route::post('/percobaan', function (Request $request) {
            $message = $request->message;
            $user = auth()->user();
            event(new ChatMessageEvent($message, $user));
            return response()->json('okk');
        });
        Route::post('/percobaan-handler', function (Request $request) {
            $message = $request->message;
            $user = auth()->user();
            event(new ChatMessageEvent($message, $user));
            return response()->json('okk');
        });
    });
