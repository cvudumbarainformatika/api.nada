<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class RedisMonitor
{
    public function handle($request, Closure $next)
    {
        $startTime = microtime(true);
        $response = $next($request);
        $duration = microtime(true) - $startTime;

        // Log Redis statistics
        $info = Redis::info();
        Log::channel('redis')->info('Redis Stats', [
            'used_memory' => $info['used_memory_human'],
            'connected_clients' => $info['connected_clients'],
            'total_commands_processed' => $info['total_commands_processed'],
            'request_duration' => round($duration * 1000, 2) . 'ms',
            'endpoint' => $request->path(),
        ]);

        return $response;
    }
}