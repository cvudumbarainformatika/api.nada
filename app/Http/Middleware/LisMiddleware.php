<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LisMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        date_default_timezone_set('Asia/Jakarta');
        $xid = '4444';
        $xtimestamp = time();
        $secret_key = 'l15Test';
        // $sign = hash_hmac('sha256', $xtimestamp, $secret_key);
        // $xsignature = base64_encode($sign);
        $expired = strtotime("+2 days", $xtimestamp);
        $checkExpired = $expired <= $request->header('X-timestamp');


        if (!$this->hasCorrectSignature($request) ) {
            return response()->json(['status' => 'signature is Invalid', 'message'=> $this->getSignature()], 401);
        }
        if ($request->header('X-id') !== $xid || !$request->headers->has('X-id')) {
            return response()->json(['status' => 'Token is Invalid', 'message'=> 'Unauthorized.'], 401);
        }
        if ($expired <= $request->header('X-timestamp') || !$request->headers->has('X-timestamp')) {
            return response()->json(['status' => 'Token is Expired', 'message'=> 'Unauthorized.'], 401);
        }

        return $next($request);
    }

    public function hasCorrectSignature($request)
    {
        $xid = '4444';
        $xtimestamp = time();
        $secret_key = 'l15Test';

        $expired = strtotime("+2 days", $xtimestamp);
        $checkExpired = $expired <= $request->header('X-timestamp');
        $signature = hash_hmac('sha256', $xid, $secret_key);

        return hash_equals($signature, (string) $request->header('X-signature'));
    }

    public function getSignature()
    {
        $xid = '4444';
        $secret_key = 'l15Test';

        // $expired = strtotime("+2 days", $xtimestamp);
        // $checkExpired = $expired <= $request->header('X-timestamp');
        $signature = hash_hmac('sha256', $xid, $secret_key);
        return $signature;
    }
}
