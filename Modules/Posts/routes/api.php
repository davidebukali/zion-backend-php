<?php

use Illuminate\Support\Facades\Route;
use Modules\Posts\Http\Controllers\PostsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::post('posts', [PostsController::class, 'store'])->name('post.store');
    Route::get('posts', [PostsController::class, 'index'])->name('post.index');
    Route::get('posts/{id}', [PostsController::class, 'show'])->name('post.show');
    Route::delete('posts/{id}', [PostsController::class, 'destroy'])->name('post.destroy');
});
