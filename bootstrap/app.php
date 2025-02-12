<?php

use App\Http\Middleware\Cors;
use App\Http\Middleware\Authenticate;
use Illuminate\Foundation\Application;
use Illuminate\Auth\Middleware\Authorize;
use App\Http\Middleware\BlockIPMiddleware;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\BlockAdminIPMiddleware;
use App\Http\Middleware\CustomThrottleRequests;
use Illuminate\Http\Middleware\SetCacheHeaders;
use App\Http\Middleware\RedirectIfAuthenticated;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'auth' => Authenticate::class,
            'auth.basic' => AuthenticateWithBasicAuth::class,
            'bindings' => SubstituteBindings::class,
            'cache.headers' => SetCacheHeaders::class,
            'can' => Authorize::class,
            'guest' => RedirectIfAuthenticated::class,
            'signed' => ValidateSignature::class,
            'throttle' => ThrottleRequests::class,
            'verified' => EnsureEmailIsVerified::class,
            'json.response' => ForceJsonResponse::class,
            'cors' => Cors::class,
            'custom.throttle' => CustomThrottleRequests::class,
            'block_ip' => BlockIPMiddleware::class,
            'block_admin_ip' => BlockAdminIPMiddleware::class,
            \Laravel\Passport\Http\Middleware\CheckClientCredentials::class,
        ]);

        $middleware->group('api', [
            'throttle:60,1',
            'bindings',
        ]);

        $middleware->priority([
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\Authenticate::class,
            \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \Illuminate\Auth\Middleware\Authorize::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
