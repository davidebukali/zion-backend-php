<?php

use Illuminate\Support\Facades\Route;
use Modules\SocialGraph\Http\Controllers\SocialGraphController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::post('users/{user}/follow', [SocialGraphController::class, 'follow'])->name('users.follow');
    Route::delete('users/{user}/follow', [SocialGraphController::class, 'unfollow'])->name('users.unfollow');

    Route::get('users/{user}/followers', [SocialGraphController::class, 'getFollowers'])->name('users.followers');
    Route::get('users/{user}/following', [SocialGraphController::class, 'getFollowing'])->name('users.following');

    Route::post('follows/{follow}/accept', [SocialGraphController::class, 'acceptFollowRequest'])->name('follows.accept');
    Route::post('follows/{follow}/reject', [SocialGraphController::class, 'rejectFollowRequest'])->name('follows.reject');
});
