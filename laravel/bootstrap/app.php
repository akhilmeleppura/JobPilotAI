<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeadersMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            // Render exact error for diagnostic debugging
            return response(
                "<!DOCTYPE html><html><head><title>JobPilot Debug</title><style>body{font-family:sans-serif;padding:2rem;background:#0f172a;color:#f8fafc;}pre{background:#1e293b;padding:1rem;border-radius:8px;overflow:auto;}</style></head><body>" .
                "<h1>Application Debug Diagnostics</h1>" .
                "<h2 style='color:#ef4444;'>" . htmlspecialchars($e->getMessage()) . "</h2>" .
                "<p><strong>Exception:</strong> " . get_class($e) . "</p>" .
                "<p><strong>File:</strong> " . htmlspecialchars($e->getFile() . ':' . $e->getLine()) . "</p>" .
                "<h3>Stack Trace:</h3><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>" .
                "</body></html>",
                500,
                ['Content-Type' => 'text/html']
            );
        });
    })->create();
