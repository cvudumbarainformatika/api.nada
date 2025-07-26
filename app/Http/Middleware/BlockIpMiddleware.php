<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BlockIpMiddleware
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public $blockIps = [
        '127.0.0.1',
        '182.23.52.28',
        '192.168.0.254',
        '192.168.100.1'
    ];
    public function handle(Request $request, Closure $next)
    {
        if (in_array($request->ip(), $this->blockIps)) {

            abort(403, "You are restricted to access the site.");
        }
        return $next($request);
    }
}
