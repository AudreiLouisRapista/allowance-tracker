<?php

use App\Http\Controllers\Auth\FamilyMemberController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\StudentController;
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
    Route::get('family-members/add', [FamilyMemberController::class, 'redirectToGoogle'])->name('family-members.add');

    // Creates the selected role inside the CURRENT family database (no new database).
    Route::post('family-members/register', [FamilyMemberController::class, 'store'])->name('family-members.register');

    // Cancels the held Google details without creating anything.
    Route::post('family-members/register/cancel', [FamilyMemberController::class, 'cancel'])->name('family-members.cancel');

    Route::get('students', [StudentController::class, 'index'])->name('students.index');      
    Route::get('archives', [StudentController::class, 'showArchives'])->name('students.archive');            
    Route::post('students', [StudentController::class, 'store'])->name('students.store');           
    Route::patch('students/{id}', [StudentController::class, 'update'])->whereNumber('id')->name('students.update'); 
    Route::patch('students/{id}/restore', [StudentController::class, 'restore'])->whereNumber('id')->name('students.restore'); 
    Route::delete('students/{id}', [StudentController::class, 'destroy'])->whereNumber('id')->name('students.destroy');
});

Route::middleware(['jwt.auth', 'role:student'])->group(function () {
    Route::inertia('student/dashboard', 'student/dashboard')->name('student.dashboard');
});

require __DIR__.'/settings.php';
