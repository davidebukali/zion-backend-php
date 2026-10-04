<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AuthController;
use Modules\Auth\Http\Controllers\ProfileController;

Route::prefix('v1')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->name('register');
    Route::post('login', [AuthController::class, 'login'])->name('login');

    Route::get('users/{user}/profile', [ProfileController::class, 'show'])->name('users.profile');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me'])->name('me');
        Route::match(['put', 'patch'], 'profile', [ProfileController::class, 'update'])->name('profile.update');
    });
});
