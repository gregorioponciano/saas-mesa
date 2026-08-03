<?php

declare(strict_types=1);

use App\Http\Controllers\Api\DeliveryController;
use App\Http\Controllers\Api\DeliveryInvitationController;
use App\Http\Controllers\Api\OrderTrackingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Superadmin\AuditLogsController;
use App\Http\Controllers\Superadmin\BackupsController;
use App\Http\Controllers\Superadmin\FinancialController;
use App\Http\Controllers\Superadmin\LoyaltyController;
use App\Http\Controllers\Superadmin\PlansController;
use App\Http\Controllers\Superadmin\SystemReportController;
use App\Http\Controllers\Superadmin\TenantsController;
use App\Http\Controllers\Superadmin\TenantSettingsController;
use App\Http\Controllers\Superadmin\UsersController;
use App\Http\Controllers\Superadmin\WebhookLogsController;
use App\Http\Controllers\Tenant\EfiCredentialsController;
use App\Http\Controllers\Tenant\FinancialController as TenantFinancialController;
use App\Http\Controllers\Tenant\PaymentController;
use Illuminate\Support\Facades\Route;

// AUTH
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/refresh', [AuthController::class, 'refreshToken'])->middleware('throttle:5,1');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');
});

// DELIVERY API (mobile) - Invitation (no auth required)
Route::prefix('delivery')->group(function () {
    Route::get('invitation/{token}', [DeliveryInvitationController::class, 'show'])->middleware('throttle:10,1');
    Route::post('invitation/{token}', [DeliveryInvitationController::class, 'accept'])->middleware('throttle:10,1');
});

// DELIVERY API (mobile) - Authenticated (controller handles auth manually)
// Compatible with both Sanctum tokens and legacy api_token
Route::prefix('delivery')->middleware('throttle:60,1')->group(function () {
    Route::post('logout', [DeliveryController::class, 'logout']);
    Route::get('orders', [DeliveryController::class, 'orders']);
    Route::get('my-orders', [DeliveryController::class, 'myOrders']);
    Route::post('orders/{order}/accept', [DeliveryController::class, 'acceptOrder']);
    Route::post('orders/{order}/refuse', [DeliveryController::class, 'refuseOrder']);
    Route::post('orders/{order}/pickup', [DeliveryController::class, 'pickupOrder']);
    Route::post('orders/{order}/status', [DeliveryController::class, 'updateStatus']);
    Route::get('profile', [DeliveryController::class, 'profile']);
});

// DELIVERY API (mobile) - Login (throttled separately)
Route::prefix('delivery')->group(function () {
    Route::get('login', function () {
        if (request()->expectsJson()) {
            return response()->json(['message' => 'Use POST para autenticar'], 405);
        }

        return redirect()->route('delivery.login');
    })->middleware('throttle:10,1');
    Route::post('login', [DeliveryController::class, 'login'])->middleware('throttle:10,1');
});

// Public Order Tracking API (no auth)
Route::get('/pedido/{id}/status', [OrderTrackingController::class, 'status'])
    ->name('api.order.tracking.status')
    ->whereNumber('id');

// SUPERADMIN (rate-limited: leituras 120/min, ações sensíveis 20/min)
Route::prefix('superadmin')
    ->middleware(['auth', 'role:superadmin', 'throttle:superadmin'])
    ->group(function () {
        Route::apiResource('plans', PlansController::class);
        Route::apiResource('tenants', TenantsController::class)->only(['index', 'show', 'store', 'destroy']);
        Route::apiResource('users', UsersController::class)->only(['index', 'store']);
        Route::post('users/{user}/revoke', [UsersController::class, 'revoke']);
        Route::get('financial/overview', [FinancialController::class, 'overview']);
        Route::get('system/report', [SystemReportController::class, 'report']);
        Route::get('financial/payments', [FinancialController::class, 'payments']);
        Route::get('financial/subscriptions', [FinancialController::class, 'subscriptions']);
        Route::get('financial/invoices', [FinancialController::class, 'invoices']);
        Route::get('financial/tenant/{tenant}', [FinancialController::class, 'tenant']);
        Route::get('financial/pix', [FinancialController::class, 'pixCharges']);
        Route::post('financial/pix/{charge}/confirm', [FinancialController::class, 'confirmPix']);
        Route::get('loyalty', [LoyaltyController::class, 'index']);
        Route::get('backups', [BackupsController::class, 'index']);
        Route::get('tenants/{tenant}/settings', [TenantSettingsController::class, 'show']);
        Route::get('webhook-logs', [WebhookLogsController::class, 'index']);
        Route::get('webhook-logs/{log}', [WebhookLogsController::class, 'show']);
        Route::get('audit-logs', [AuditLogsController::class, 'index']);

        // Ações sensíveis/destrutivas: teto mais baixo (20/min por usuário/IP)
        Route::middleware('throttle:superadmin-sensitive')->group(function () {
            Route::post('tenants/{tenant}/suspend', [TenantsController::class, 'suspend']);
            Route::post('tenants/{tenant}/reactivate', [TenantsController::class, 'reactivate']);
            Route::put('tenants/{tenant}/plan', [TenantsController::class, 'changePlan']);
            Route::post('tenants/{tenant}/force-charge', [TenantsController::class, 'forceCharge']);
            Route::get('tenants/{tenant}/export', [TenantsController::class, 'export']);
            Route::delete('backups/{backup}', [BackupsController::class, 'destroy']);
            Route::post('backups', [BackupsController::class, 'store']);
            Route::post('loyalty/{tenant}/toggle', [LoyaltyController::class, 'toggle']);
            Route::put('tenants/{tenant}/settings', [TenantSettingsController::class, 'update']);
        });
    });

// TENANT API
Route::middleware(['auth', 'resolve.tenant', 'check.tenant.subscription', 'throttle:60,1'])->group(function () {
    Route::post('orders/{order}/pay', [PaymentController::class, 'initiate']);
    Route::get('orders/{order}/payment/status', [PaymentController::class, 'status']);
    Route::get('orders/{order}/payment/qrcode', [PaymentController::class, 'qrcode']);

    Route::middleware('check.admin')->group(function () {
        Route::get('settings/efi-credentials', [EfiCredentialsController::class, 'show']);
        Route::put('settings/efi-credentials', [EfiCredentialsController::class, 'update']);
        Route::post('settings/efi-credentials/test', [EfiCredentialsController::class, 'test']);
    });

    Route::middleware('check.admin')->group(function () {
        Route::get('financial/payments', [TenantFinancialController::class, 'payments']);
        Route::get('financial/summary', [TenantFinancialController::class, 'summary']);
    });
});
