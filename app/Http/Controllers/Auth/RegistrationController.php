<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\JwtService;
use App\Services\TenantRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class RegistrationController extends Controller
{
    /**
     * The person clicked "Create account" in the confirmation dialog.
     */
    public function store(
        Request $request,
        TenantRegistrationService $registrationService,
        JwtService $jwtService): RedirectResponse
    {
        // 1. Read the Google details that the callback held in the session.
        $pending = $request->session()->get('pending_registration');

        // 2. Nothing held, or held for more than 10 minutes: ask them to sign in again.
        if ($pending === null || $pending['expires_at'] < now()->timestamp) {
            $request->session()->forget('pending_registration');

            return redirect('/login')->with('google_error', 'Your sign-in expired. Please sign in with Google again.');
        }

        // 3. If this email got registered in the meantime, send them through the normal sign-in instead.
        if (DB::table('users_directory')->where('email', $pending['email'])->exists()) {
            $request->session()->forget('pending_registration');

            return redirect('/auth/google/redirect');
        }

        // 4. Create the family, its database, and the first parent. On failure it cleans up after itself.
        try {
            $result = $registrationService->register($pending['email'], $pending['google_id'], $pending['name']);
        } catch (Throwable $exception) {
            report($exception);   // Write the real error to storage/logs/laravel.log.

            return redirect('/login')->with('google_error', 'We could not set up your account. Please try again.');
        }

        // 5. Done: forget the held details.
        $request->session()->forget('pending_registration');

        // 6. Sign the new parent in: create the JWT and keep it in the httpOnly cookie.
        $token = $jwtService->createToken($result['user_id'], $result['family_id'], $result['role']);

        $cookie = cookie(
            name: config('jwt.cookie_name'),
            value: $token,
            minutes: config('jwt.ttl_minutes'),
            path: '/',
            domain: null,
            secure: $request->isSecure(),
            httpOnly: true,
            raw: false,
            sameSite: 'lax',
        );

        // 7. Go to the dashboard, with the "new account" welcome message.
        return redirect()
            ->route('parent.dashboard')
            ->with('welcome', ['type' => 'new', 'name' => $pending['name']])
            ->withCookie($cookie);
    }

    /**
     * The person clicked "Cancel": throw the held details away. Nothing was created.
     */
    public function cancel(Request $request): RedirectResponse
    {
        $request->session()->forget('pending_registration');

        return redirect('/login');
    }
}
