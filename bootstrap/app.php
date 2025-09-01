<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (\Illuminate\Foundation\Configuration\Middleware $middleware) {
        $middleware->trustProxies(at: '*');
//        $headers = SymfonyRequest::HEADER_X_FORWARDED_FOR
//            | SymfonyRequest::HEADER_X_FORWARDED_HOST
//            | SymfonyRequest::HEADER_X_FORWARDED_PROTO
//            | SymfonyRequest::HEADER_X_FORWARDED_PORT;

        $middleware->alias([
            'hapi.js' => \App\Http\Middleware\VerifyHapiSignature::class,
//            'force-http' => \App\Http\Middleware\ForceHTTPS::class,
        ]);

//        $middleware->trustProxies(at: '*', headers: $headers);

        $middleware->trustHosts([
            '^hris-admin-709127657420\.asia-southeast2\.run\.app$',
            '^hris-admin-sg-709127657420\.asia-southeast1\.run\.app$',
            '^(.+\.)?indraangkola\.com$'
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
