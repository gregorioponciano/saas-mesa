<?php

declare(strict_types=1);

use App\Http\Middleware\BlockSuperadminFromTenantPanel;
use App\Http\Middleware\CheckAdminRole;
use App\Http\Middleware\CheckPaidTenant;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\CheckStaffRole;
use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\CheckTenantOwner;
use App\Http\Middleware\CheckTenantSubscription;
use App\Http\Middleware\ResolveTenant;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\TenantScopeMiddleware;
use App\Http\Middleware\ValidateWebhookSignature;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(function ($request) {
            if ($request->expectsJson()) {
                return null;
            }
            if ($request->is('superadmin*')) {
                return route('superadmin.login');
            }
            if ($request->is('entregador/*')) {
                return route('delivery.login');
            }

            return route('login');
        });

        $middleware->alias([
            'tenant.scope' => TenantScopeMiddleware::class,
            'check.subscription' => CheckSubscription::class,
            'check.tenant.subscription' => CheckTenantSubscription::class,
            'block.superadmin.from.tenant.panel' => BlockSuperadminFromTenantPanel::class,
            'check.admin' => CheckAdminRole::class,
            'check.staff' => CheckStaffRole::class,
            'check.tenant.owner' => CheckTenantOwner::class,
            'check.paid.tenant' => CheckPaidTenant::class,
            'resolve.tenant' => ResolveTenant::class,
            'security.headers' => SecurityHeaders::class,
            'validate.webhook.signature' => ValidateWebhookSignature::class,
            'role' => CheckRole::class,
        ]);

        $middleware->api(prepend: [
            SecurityHeaders::class,
            EnsureFrontendRequestsAreStateful::class,
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
