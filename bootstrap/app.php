<?php

use App\Http\Middleware\CheckAdminRole;
use App\Http\Middleware\CheckStaffRole;
use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\TenantScopeMiddleware;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant.scope' => TenantScopeMiddleware::class,
            'check.subscription' => CheckSubscription::class,
            'check.admin' => CheckAdminRole::class,
            'check.staff' => CheckStaffRole::class,
            'staff.access' => \App\Http\Middleware\EnsureStaffAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
