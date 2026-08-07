<?php

use Illuminate\Support\Facades\Route;
use Modules\Posts\Http\Controllers\PostsController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('posts', PostsController::class)->names('posts');
});
