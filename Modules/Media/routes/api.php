<?php

use Illuminate\Support\Facades\Route;
use Modules\Media\Http\Controllers\MediaController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::post(
        '/media/upload-url',
        [MediaController::class, 'uploadUrl']
    )->middleware('throttle:media-upload');

    Route::post(
        '/media/{media}/confirm',
        [MediaController::class, 'confirm']
    );

    Route::delete(
        '/media/{media}',
        [MediaController::class, 'destroy']
    );
});
