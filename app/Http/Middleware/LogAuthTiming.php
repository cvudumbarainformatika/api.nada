<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Support\Facades\Log;

class LogAuthTiming extends Middleware
{
    public function handle($request, Closure $next, ...$guards)
    {
        $start = microtime(true);

        // Jalankan proses autentikasi
        $this->authenticate($request, $guards);

        // Log waktu yang dibutuhkan
        Log::info('⏱ Auth middleware time: ' . (microtime(true) - $start));

        return $next($request);
    }
}
