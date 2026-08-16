<?php

use Illuminate\Support\Facades\Route;
use Modules\Interactions\Http\Controllers\InteractionsController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('interactions', InteractionsController::class)->names('interactions');
});
