<?php

declare(strict_types=1);

use App\Http\Middleware\CheckAdminRole;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\CheckStaffRole;
use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\CheckTenantSubscription;
use App\Http\Middleware\ResolveTenant;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\TenantScopeMiddleware;
use App\Http\Middleware\ValidateWebhookSignature;
use App\Models\SaasSubscription;
use App\Observers\SaasSubscriptionObserver;
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
        $middleware->alias([
            'tenant.scope' => TenantScopeMiddleware::class,
            'check.subscription' => CheckSubscription::class,
            'check.tenant.subscription' => CheckTenantSubscription::class,
            'check.admin' => CheckAdminRole::class,
            'check.staff' => CheckStaffRole::class,
            'staff.access' => \App\Http\Middleware\EnsureStaffAccess::class,
            'resolve.tenant' => ResolveTenant::class,
            'security.headers' => SecurityHeaders::class,
            'validate.webhook.signature' => ValidateWebhookSignature::class,
            'role' => CheckRole::class,
        ]);

    
        $middleware->api(prepend: [
            SecurityHeaders::class,
        ]);

        $middleware->web(prepend: [
            SecurityHeaders::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'webhook/efi/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
