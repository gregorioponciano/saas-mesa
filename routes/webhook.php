<?php

declare(strict_types=1);

use App\Http\Controllers\Webhook\SaasWebhookController;
use App\Http\Controllers\Webhook\TenantWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('webhook/efi')
    ->middleware(['validate.webhook.signature'])
    ->group(function () {
        Route::post('/saas', [SaasWebhookController::class, 'handle']);
        Route::post('/tenant/{tenantId}', [TenantWebhookController::class, 'handle']);
    });
