<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegistrationController;
use Illuminate\Support\Facades\Route;

Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);

Route::post('/auth/register', [RegistrationController::class, 'store']);
Route::post('/auth/register/cancel', [RegistrationController::class, 'cancel']);

// Our own logout address. The starter kit has its own /logout, which we leave alone.
Route::post('/auth/logout', LogoutController::class);

Route::inertia('/', 'welcome')->name('home');

Route::middleware('jwt.auth')->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';