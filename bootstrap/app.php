<?php

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
        // ✅ REGISTRAR ALIAS DE MIDDLEWARES
        // Aquí mantenemos tus validaciones de Admin y Auth
        $middleware->alias([
            'check.admin' => \App\Http\Middleware\IsAdmin::class,
            'user.auth' => \App\Http\Middleware\IsUserAuth::class,
        ]);

        // ✅ EXCEPCIÓN DE CSRF PARA LA API
        // Esto es vital para que las peticiones POST desde React no reboten
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
