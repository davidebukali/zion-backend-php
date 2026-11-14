<?php

use App\Http\Controllers\AccountDeletionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('/privacy', 'legal.privacy')->name('privacy');
Route::view('/terms', 'legal.terms')->name('terms');
Route::view('/community-guidelines', 'legal.community-guidelines')->name('community-guidelines');

Route::get('/delete-account', [AccountDeletionController::class, 'showForm'])->name('account.delete.form');
Route::post('/delete-account', [AccountDeletionController::class, 'submit'])
    ->middleware('throttle:5,1')
    ->name('account.delete.submit');
