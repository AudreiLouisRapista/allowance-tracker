<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);

Route::post('/auth/register', [RegistrationController::class, 'store']);
Route::post('/auth/register/cancel', [RegistrationController::class, 'cancel']);

Route::post('/auth/logout', LogoutController::class);

Route::inertia('/', 'welcome')->name('home');

Route::middleware('jwt.auth', 'role:parent')->group(function () {
    
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::inertia('settings/appearance', 'settings/appearance')->name('appearance.edit');

});


require __DIR__.'/settings.php';