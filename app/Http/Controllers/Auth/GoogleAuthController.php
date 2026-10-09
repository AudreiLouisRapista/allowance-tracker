<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\JwtService;
use App\Services\TenantConnection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    /**
     * Send the user to Google's sign-in page.
     */
    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Google sends the user back here after they approve (or cancel).
     */
    public function handleGoogleCallback(
        Request $request,
        JwtService $jwtService,
        TenantConnection $tenantConnection
    ): RedirectResponse {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $exception) {
            // Cancelled consent, expired state, or a Google error. Log it and send the user back.
            report($exception);

            return $this->backToLogin('Google sign-in was cancelled or failed. Please try again.');
        }

        $email = $googleUser->getEmail();
        $googleId = $googleUser->getId();

        // Refuse only if Google explicitly says the email is not verified.
        $emailIsVerified = $googleUser->user['email_verified'] ?? true;

        if (empty($email) || $emailIsVerified === false) {
            return $this->backToLogin('Your Google email could not be verified.');
        }

        // Step 1: find which family this email belongs to (master database).
        $directoryEntry = DB::table('users_directory')->where('email', $email)->first();

        if ($directoryEntry === null) {
            return $this->backToLogin('This Google account is not registered yet.');
        }

        // Step 2: point the tenant connection at that family's database.
        $tenantConnection->switchToFamily($directoryEntry->family_id);

        // Step 3: find the user inside the family database.
        $user = DB::connection(TenantConnection::CONNECTION)
            ->table('users')
            ->join('user_status', 'user_status.id', '=', 'users.usr_status_id')
            ->join('user_role', 'user_role.id', '=', 'users.usr_role_id')
            ->where('users.email', $email)
            ->select('users.id', 'users.google_id', 'user_status.status as status', 'user_role.role as role')
            ->first();

        if ($user === null) {
            return $this->backToLogin('This Google account is not registered yet.');
        }

        if ($user->status !== 'active') {
            return $this->backToLogin('This account has been removed from the family.');
        }

        // Step 4: first sign-in saves the Google ID. Later sign-ins must match it.
        if ($user->google_id === null) {
            DB::connection(TenantConnection::CONNECTION)
                ->table('users')
                ->where('id', $user->id)
                ->update(['google_id' => $googleId]);
        } elseif ($user->google_id !== $googleId) {
            return $this->backToLogin('This Google account does not match the one registered for this user.');
        }

        // Step 5: create the token and store it in an httpOnly cookie.
        $token = $jwtService->createToken($user->id, $directoryEntry->family_id, $user->role);

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

        // Temporary target for testing. The React step replaces it with the real dashboard.
        return redirect('/auth/me')->withCookie($cookie);
    }

    private function backToLogin(string $message): RedirectResponse
    {
        return redirect('/login')->with('google_error', $message);
    }
}