<?php

use Illuminate\Support\Facades\Route;
use Modules\Interactions\Http\Controllers\PostLikeController;
use Modules\Interactions\Http\Controllers\CommentLikeController;
use Modules\Interactions\Http\Controllers\PostShareController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::post('/posts/{post}/like', [PostLikeController::class, 'likePost'])->name('post.like');
    Route::delete('/posts/{post}/like', [PostLikeController::class, 'unlikePost'])->name('post.unlike');

    Route::post('/posts/{post}/internal-share', [PostShareController::class, 'internalSharePost'])->name('post.internal-share');
    Route::post('/posts/{post}/external-share', [PostShareController::class, 'externalSharePost'])->name('post.external-share');
    Route::delete('/posts/{post}/share', [PostShareController::class, 'unsharePost'])->name('post.unshare');

    Route::post('/comments/{comment}/like', [CommentLikeController::class, 'likeComment'])->name('comment.like');
    Route::delete('/comments/{comment}/like', [CommentLikeController::class, 'unlikeComment'])->name('comment.unlike');
});
