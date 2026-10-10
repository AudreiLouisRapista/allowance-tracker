<?php

use Illuminate\Support\Facades\Route;

Route::get('/auth/google/redirect', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'handleGoogleCallback']);

// Temporary test route. Shows who is signed in.
Route::get('/auth/me', function (\Illuminate\Http\Request $request) {
    return response()->json([
        'user' => $request->attributes->get('auth_user'),
        'family_id' => $request->attributes->get('auth_family_id'),
    ]);
})->middleware('jwt.auth');

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});



require __DIR__.'/settings.php';
