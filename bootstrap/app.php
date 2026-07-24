<?php
// bootstrap/app.php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // ← أضيفي هاد السطر

    // تسجيل الميدل وير كأسماء مختصرة
    $middleware->alias([
        'admin'    => \App\Http\Middleware\AdminMiddleware::class,
        'delivery' => \App\Http\Middleware\DeliveryMiddleware::class,
        'user'     => \App\Http\Middleware\UserMiddleware::class,
    
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();