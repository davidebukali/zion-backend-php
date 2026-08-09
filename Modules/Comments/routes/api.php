<?php

use Illuminate\Support\Facades\Route;
use Modules\Comments\Http\Controllers\CommentsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::post('posts/{post}/comments', [CommentsController::class, 'store'])->name('comments.store');
    Route::get('posts/{post}/comments', [CommentsController::class, 'index'])->name('comments.index');
    Route::patch('comments/{comment}', [CommentsController::class, 'update'])->name('comments.update');
    Route::delete('comments/{comment}', [CommentsController::class, 'destroy'])->name('comments.destroy');
});
