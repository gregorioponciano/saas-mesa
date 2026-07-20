<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Superadmin\FinancialController;
use App\Http\Controllers\Superadmin\LoyaltyController;
use App\Http\Controllers\Superadmin\PlansController;
use App\Http\Controllers\Superadmin\TenantsController;
use App\Http\Controllers\Tenant\EfiCredentialsController;
use App\Http\Controllers\Tenant\FinancialController as TenantFinancialController;
use App\Http\Controllers\Tenant\PaymentController;
use App\Http\Controllers\Api\DeliveryController;
use Illuminate\Support\Facades\Route;

// ─── AUTH ───
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');
    Route::post('/refresh', [AuthController::class, 'refreshToken'])
        ->middleware('throttle:5,1');
    Route::post('/logout', [AuthController::class, 'logout'])
        ->middleware('auth');
});

// ─── DELIVERY API (mobile) ───
Route::prefix('delivery')->group(function () {
    Route::post('login', [DeliveryController::class, 'login'])->middleware('throttle:10,1');
    Route::middleware(['auth', 'throttle:60,1'])->group(function () {
        Route::get('orders', [DeliveryController::class, 'orders']);
        Route::get('my-orders', [DeliveryController::class, 'myOrders']);
        Route::post('orders/{order}/accept', [DeliveryController::class, 'acceptOrder']);
        Route::post('orders/{order}/status', [DeliveryController::class, 'updateStatus']);
        Route::get('profile', [DeliveryController::class, 'profile']);
    });
});

// ─── SUPERADMIN (apenas Gregório) ───
Route::prefix('superadmin')
    ->middleware(['auth', 'role:superadmin'])
    ->group(function () {
        Route::apiResource('plans', PlansController::class);
        Route::apiResource('tenants', TenantsController::class)->only(['index', 'show']);
        Route::get('financial/overview', [FinancialController::class, 'overview']);
        Route::get('financial/payments', [FinancialController::class, 'payments']);
        Route::get('financial/tenant/{tenant}', [FinancialController::class, 'tenant']);

        // Tenant management actions
        Route::post('tenants/{tenant}/suspend', [TenantsController::class, 'suspend']);
        Route::post('tenants/{tenant}/reactivate', [TenantsController::class, 'reactivate']);
        Route::put('tenants/{tenant}/plan', [TenantsController::class, 'changePlan']);
        Route::post('tenants/{tenant}/force-charge', [TenantsController::class, 'forceCharge']);

        // Loyalty points management
        Route::get('loyalty', [LoyaltyController::class, 'index']);
        Route::post('loyalty/{tenant}/toggle', [LoyaltyController::class, 'toggle']);
    });

// ─── TENANT API ───
Route::middleware(['auth', 'resolve.tenant', 'check.tenant.subscription', 'throttle:60,1'])->group(function () {
    // Payment operations (any authenticated user can pay)
    Route::post('orders/{order}/pay', [PaymentController::class, 'initiate']);
    Route::get('orders/{order}/payment/status', [PaymentController::class, 'status']);
    Route::get('orders/{order}/payment/qrcode', [PaymentController::class, 'qrcode']);

    // EfiBank credentials management (admin only)
    Route::middleware('check.admin')->group(function () {
        Route::get('settings/efi-credentials', [EfiCredentialsController::class, 'show']);
        Route::put('settings/efi-credentials', [EfiCredentialsController::class, 'update']);
        Route::post('settings/efi-credentials/test', [EfiCredentialsController::class, 'test']);
    });

    // Financial reports (admin only)
    Route::middleware('check.admin')->group(function () {
        Route::get('financial/payments', [TenantFinancialController::class, 'payments']);
        Route::get('financial/summary', [TenantFinancialController::class, 'summary']);
    });
});
