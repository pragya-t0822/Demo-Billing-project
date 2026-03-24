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
    ->withMiddleware(function (Middleware $middleware): void {
        // CORS — must run before auth so preflight OPTIONS requests are handled
        $middleware->prepend(\Illuminate\Http\Middleware\HandleCors::class);

        // Register route-level middleware aliases
        $middleware->alias([
            'role'  => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'audit' => \App\Http\Middleware\AuditMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle business rule exceptions with clean JSON responses
        $exceptions->render(function (\App\Exceptions\JournalImbalanceException $e) {
            return response()->json(['success' => false, 'error' => ['code' => 'JOURNAL_IMBALANCE', 'message' => $e->getMessage()]], 422);
        });

        $exceptions->render(function (\App\Exceptions\InsufficientStockException $e) {
            return response()->json(['success' => false, 'error' => ['code' => 'INSUFFICIENT_STOCK', 'message' => $e->getMessage()]], 422);
        });

        $exceptions->render(function (\App\Exceptions\BusinessRuleException $e) {
            return response()->json(['success' => false, 'error' => ['code' => $e->getRule() ?: 'BUSINESS_RULE_VIOLATION', 'message' => $e->getMessage()]], 422);
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'error' => ['code' => 'VALIDATION_ERROR', 'message' => 'Validation failed.', 'details' => $e->errors()]], 422);
        });
    })->create();
