<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Services\TenantConnection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->attributes->get('auth_user');

        $phoneNumber = DB::connection(TenantConnection::CONNECTION)->table('users')
            ->where('id', $user->id)
            ->value('phonenumber');
        
        return Inertia::render('settings/profile', ['phoneNumber' => $phoneNumber]);
    }

    /**
     * Save the signed-in user's name and phone number.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->attributes->get('auth_user');

        // Check the input. Empty values arrive as null, which "nullable" allows for the phone.
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],        // The column holds at most 150 characters.
            'phonenumber' => ['nullable', 'digits_between:1,10'], // Digits only, 1 to 10 of them.
        ], [
            'phonenumber.digits_between' => 'Enter 1 to 10 digits, numbers only. Do not include +63.',
        ]);

        // Update only these two fields of this user's row. The email is never touched.
        DB::connection(TenantConnection::CONNECTION)
            ->table('users')
            ->where('id', $user->id)
            ->update([
                'name' => $validated['name'],
                'phonenumber' => $validated['phonenumber'] ?? null,
            ]);

        // Show the "Profile updated." message (same line the starter kit used).
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated.')]);

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
