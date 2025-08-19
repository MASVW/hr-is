<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyHapiSignature
{
    public function handle(Request $request, Closure $next)
    {
        $secret = config('services.hapi.shared_secret');
        if (! $secret) abort(500, 'Hapi secret not set');

        $ts  = $request->header('X-Timestamp');
        $sig = $request->header('X-Signature');

        if (! $ts || ! $sig) abort(401, 'Missing signature');

        if (abs(time() - (int) $ts) > 300) abort(401, 'Stale timestamp');

        $base = strtoupper($request->method()) . "\n"
            . $request->getPathInfo()    . "\n"
            . $ts                        . "\n"
            . $request->getContent();

        $expected = base64_encode(hash_hmac('sha256', $base, $secret, true));

        if (! hash_equals($expected, $sig)) abort(401, 'Invalid signature');

        return $next($request);
    }
}
