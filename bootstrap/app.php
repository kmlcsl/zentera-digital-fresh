<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Ensure CSRF is properly configured
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);

        // Trust Vercel proxy
        $middleware->trustProxies(at: '*');

        // Ensure web middleware includes CSRF
        $middleware->web([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        // // Register Admin Middleware Aliases
        // $middleware->alias([
        //     'admin' => \App\Http\Middleware\AdminMiddleware::class,
        //     'admin.guest' => \App\Http\Middleware\RedirectIfAdmin::class,
        //     'admin.role' => \App\Http\Middleware\AdminRoleMiddleware::class,
        // ]);

        // // Optional: Register middleware groups
        // $middleware->group('admin-auth', [
        //     \App\Http\Middleware\AdminMiddleware::class,
        // ]);

        // $middleware->group('admin-guest', [
        //     \App\Http\Middleware\RedirectIfAdmin::class,
        // ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
