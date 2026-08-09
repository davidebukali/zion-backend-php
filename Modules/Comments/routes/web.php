<?php

use Illuminate\Support\Facades\Route;
use Modules\Comments\Http\Controllers\CommentsController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('comments', CommentsController::class)->names('comments');
});
