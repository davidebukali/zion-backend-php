<?php

use Illuminate\Support\Facades\Route;
use Modules\SocialGraph\Http\Controllers\SocialGraphController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('users/taggable', [SocialGraphController::class, 'taggableUsers'])->name('users.taggable');

    Route::post('users/{user}/follow', [SocialGraphController::class, 'follow'])->name('users.follow');
    Route::delete('users/{user}/follow', [SocialGraphController::class, 'unfollow'])->name('users.unfollow');

    Route::get('users/{user}/followers', [SocialGraphController::class, 'getFollowers'])->name('users.followers');
    Route::get('users/{user}/following', [SocialGraphController::class, 'getFollowing'])->name('users.following');

    Route::post('follows/{follow}/accept', [SocialGraphController::class, 'acceptFollowRequest'])->name('follows.accept');
    Route::post('follows/{follow}/reject', [SocialGraphController::class, 'rejectFollowRequest'])->name('follows.reject');

    Route::post('users/{user}/block', [SocialGraphController::class, 'block'])->name('users.block');
    Route::delete('users/{user}/block', [SocialGraphController::class, 'unblock'])->name('users.unblock');
    Route::get('blocks', [SocialGraphController::class, 'getBlockedUsers'])->name('blocks.index');

    Route::get('users/report-reasons', [SocialGraphController::class, 'reportReasons'])->name('users.report-reasons');
    Route::post('users/{user}/report', [SocialGraphController::class, 'report'])->name('users.report');

    Route::get('feed', [SocialGraphController::class, 'feed'])->name('feed.index');
    Route::get('feed/check-newer', [SocialGraphController::class, 'checkNewer'])->name('feed.check-newer');
});
