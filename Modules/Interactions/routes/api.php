<?php

use Illuminate\Support\Facades\Route;
use Modules\Interactions\Http\Controllers\PostLikeController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::post('/posts/{post}/like', [PostLikeController::class, 'likePost'])->name('post.like');
    Route::delete('/posts/{post}/like', [PostLikeController::class, 'unlikePost'])->name('post.unlike');
});
