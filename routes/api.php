<?php

use App\Http\Controllers\Api\DeliveryController;
use Illuminate\Support\Facades\Route;

Route::prefix('delivery')->group(function () {
    Route::post('login', [DeliveryController::class, 'login']);
    Route::get('orders', [DeliveryController::class, 'orders']);
    Route::get('my-orders', [DeliveryController::class, 'myOrders']);
    Route::post('orders/{order}/accept', [DeliveryController::class, 'acceptOrder']);
    Route::post('orders/{order}/status', [DeliveryController::class, 'updateStatus']);
    Route::get('profile', [DeliveryController::class, 'profile']);
});
