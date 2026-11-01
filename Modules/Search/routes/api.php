<?php

use Illuminate\Support\Facades\Route;
use Modules\Search\Http\Controllers\SearchController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('search', [SearchController::class, 'search'])->name('search');
});
