<?php

use Illuminate\Support\Facades\Route;
use Modules\SocialGraph\Http\Controllers\SocialGraphController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('socialgraphs', SocialGraphController::class)->names('socialgraph');
});
