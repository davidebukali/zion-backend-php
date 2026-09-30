<?php

use Illuminate\Support\Facades\Route;
use Modules\Notifications\Http\Controllers\NotificationsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('notifications', [NotificationsController::class, 'index'])->name('notifications.index');
    Route::post('notifications/{notification}/read', [NotificationsController::class, 'markAsRead'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationsController::class, 'markAllAsRead'])->name('notifications.read-all');
});
