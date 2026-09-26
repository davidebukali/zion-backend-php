<?php

use Illuminate\Support\Facades\Route;
use Modules\SocialGraph\Http\Controllers\SocialGraphController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('socialgraphs', SocialGraphController::class)->names('socialgraph');
});
