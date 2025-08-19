<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class VerifyHapiSignature
{
    public function handle(Request $request, Closure $next)
    {
        $secret = config('services.hapi.shared_secret');
        $maxSkew = (int) config('services.hapi.max_skew', 300);

        if (! $secret) {
            return response()->json(['ok' => false, 'error' => 'Hapi secret not set'], 500);
        }

        // Optional: paksa JSON
        if (! str_contains(strtolower($request->header('content-type', '')), 'application/json')) {
            return response()->json(['ok' => false, 'error' => 'Unsupported Content-Type'], 415);
        }

        $ts  = $request->header('X-Timestamp');
        $sig = $request->header('X-Signature');

        if ($ts === null || $sig === null) {
            return response()->json(['ok' => false, 'error' => 'Missing signature headers'], 401);
        }
        if (!ctype_digit((string) $ts)) {
            return response()->json(['ok' => false, 'error' => 'Invalid timestamp'], 401);
        }
        if (abs(time() - (int) $ts) > $maxSkew) {
            return response()->json(['ok' => false, 'error' => 'Stale timestamp'], 401);
        }

        // Idempotency (opsional tapi sangat berguna)
        if ($key = $request->header('X-Idempotency-Key')) {
            $cacheKey = 'idem:' . sha1($key);
            if (Cache::has($cacheKey)) {
                return response()->json(['ok' => true, 'idempotent' => true]); // sudah diproses
            }
            // tandai 10 menit
            Cache::put($cacheKey, 1, 600);
        }

        // RAW body harus persis sama dengan yang di-sign dari Hapi
        $base = strtoupper($request->method()) . "\n"
            . $request->getPathInfo() . "\n"
            . $ts . "\n"
            . $request->getContent();

        $expected = base64_encode(hash_hmac('sha256', $base, $secret, true));

        if (! hash_equals($expected, $sig)) {
            return response()->json(['ok' => false, 'error' => 'Invalid signature'], 401);
        }

        return $next($request);
    }
}
