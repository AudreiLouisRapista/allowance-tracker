<?php

use App\Http\Controllers\Auth\FamilyMemberController as AuthFamilyMemberController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\FamilyMemberController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/email/login', [GoogleAuthController::class, 'loginWithEmail']);
Route::get('/auth/google/login', [GoogleAuthController::class, 'redirectToGoogleLogin']);
Route::get('/auth/google/signup', [GoogleAuthController::class, 'redirectToGoogleSignup']);
Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);

Route::post('/auth/register', [RegistrationController::class, 'store']);
Route::post('/auth/register/cancel', [RegistrationController::class, 'cancel']);

Route::post('/auth/logout', LogoutController::class);

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['jwt.auth', 'role:parent'])->group(function () {
    Route::inertia('dashboard', 'parent/dashboard')->name('parent.dashboard');
    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::inertia('settings/appearance', 'settings/appearance')->name('appearance.edit');

    // Starts Google OAuth for a new family member. The chosen role and the
    // current family are saved on the server before redirecting to Google.
    Route::get('family-members/add', [AuthFamilyMemberController::class, 'redirectToGoogle'])->name('family-members.add');
    Route::post('family-members/register', [AuthFamilyMemberController::class, 'store'])->name('family-members.register');
    Route::post('family-members/register/cancel', [AuthFamilyMemberController::class, 'cancel'])->name('family-members.cancel');

    Route::get('family-members/archives', [FamilyMemberController::class, 'showArchives'])->name('family-members.archive');
    Route::get('family-members', [FamilyMemberController::class, 'index'])->name('family-members.index');
    Route::post('family-members', [FamilyMemberController::class, 'store'])->name('family-members.store');
    Route::patch('family-members/{id}', [FamilyMemberController::class, 'update'])->whereNumber('id')->name('family-members.update');
    Route::patch('family-members/{id}/restore', [FamilyMemberController::class, 'restore'])->whereNumber('id')->name('family-members.restore');
    Route::delete('family-members/{id}', [FamilyMemberController::class, 'destroy'])->whereNumber('id')->name('family-members.destroy');
});

Route::middleware(['jwt.auth', 'role:student'])->group(function () {
    Route::inertia('student/dashboard', 'student/dashboard')->name('student.dashboard');
});

require __DIR__.'/settings.php';
