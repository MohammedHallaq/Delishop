<?php

use App\Http\Controllers\ResponseFormatter;
use App\Http\Middleware\JwtMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
       // $middleware->append(JwtMiddleware::class);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (AccessDeniedHttpException $e,$request) {
            return ResponseFormatter::Error(
                'You do not have permission to access this page.','',
                403);
        });

    })->create();
