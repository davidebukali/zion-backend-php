<?php

use Illuminate\Support\Facades\Route;
use Modules\Posts\Http\Controllers\PostModerationWebhookController;
use Modules\Posts\Http\Controllers\PostsController;
use Modules\Posts\Http\Middleware\VerifyModerationWebhookSignature;

Route::prefix('v1')->group(function () {
    Route::post('internal/moderation/webhook', PostModerationWebhookController::class)
        ->middleware([VerifyModerationWebhookSignature::class, 'throttle:120,1'])
        ->name('internal.moderation.webhook');

    Route::get('users/{user}/posts', [PostsController::class, 'userPosts'])->name('users.posts');

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('posts', [PostsController::class, 'store'])->name('post.store');
        Route::get('posts', [PostsController::class, 'index'])->name('post.index');
        Route::get('posts/{post}', [PostsController::class, 'show'])->name('post.show');
        Route::delete('posts/{post}', [PostsController::class, 'destroy'])->name('post.destroy');
    });
});

