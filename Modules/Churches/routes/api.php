<?php

use Illuminate\Support\Facades\Route;
use Modules\Churches\Http\Controllers\ChurchesController;

Route::prefix('v1')->group(function () {
    Route::get('churches', [ChurchesController::class, 'index'])->name('churches.index');
});

