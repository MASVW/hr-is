<?php

// app/Http/Middleware/ForceHttpsFromForwarded.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceHTTPS
{
    public function handle(Request $request, Closure $next)
    {
        $proto = $request->headers->get('X-Forwarded-Proto');
        if ($proto === 'https') {
            $request->server->set('HTTPS', 'on');
        }
        return $next($request);
    }
}
