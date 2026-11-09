<?php

use Illuminate\Support\Facades\Route;
use Modules\Notifications\Http\Controllers\DeviceTokenController;
use Modules\Notifications\Http\Controllers\NotificationsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('notifications', [NotificationsController::class, 'index'])->name('notifications.index');
    Route::post('notifications/{notification}/read', [NotificationsController::class, 'markAsRead'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationsController::class, 'markAllAsRead'])->name('notifications.read-all');

    Route::post('devices/token', [DeviceTokenController::class, 'store'])->name('devices.token.store');
    Route::delete('devices/token', [DeviceTokenController::class, 'destroy'])->name('devices.token.destroy');

    Route::post('notifications/push-token', [DeviceTokenController::class, 'store'])->name('notifications.push-token.store');
    Route::delete('notifications/push-token', [DeviceTokenController::class, 'destroy'])->name('notifications.push-token.destroy');
});
