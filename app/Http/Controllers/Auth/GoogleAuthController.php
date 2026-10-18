<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\JwtService;
use App\Services\TenantConnection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class GoogleAuthController extends Controller
{
    public function loginWithEmail(
        Request $request,
        JwtService $jwtService,
        TenantConnection $tenantConnection
    ): RedirectResponse {
        $request->session()->forget('google_auth_intent');

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);
        $email = Str::lower(trim($validated['email']));

        $directoryEntry = DB::table('users_directory')
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($directoryEntry === null) {
            return $this->backToLogin(
                'No account was found for this email. Please check it or sign up.'
            );
        }

        $tenantConnection->switchToFamily($directoryEntry->family_id);

        $user = DB::connection(TenantConnection::CONNECTION)
            ->table('users')
            ->join('user_status', 'user_status.id', '=', 'users.usr_status_id')
            ->join('user_role', 'user_role.id', '=', 'users.usr_role_id')
            ->whereRaw('LOWER(users.email) = ?', [$email])
            ->select('users.id', 'users.name', 'user_status.status as status', 'user_role.role as role')
            ->first();

        if ($user === null) {
            return $this->backToLogin('We could not find this user in the registered family.');
        }

        if ($user->status !== 'active') {
            return $this->backToLogin('This account is not active.');
        }

        $dashboardRoute = match ($user->role) {
            'parent' => 'parent.dashboard',
            'student' => 'student.dashboard',
            default => null,
        };

        if ($dashboardRoute === null) {
            return $this->backToLogin('This account does not have dashboard access.');
        }

        $request->session()->forget('pending_registration');

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

        return redirect()
            ->route($dashboardRoute)
            ->with('welcome', [
                'type' => 'returning',
                'name' => $user->name,
            ])
            ->withCookie($cookie);
    }

    public function redirectToGoogleSignup(Request $request): SymfonyRedirectResponse
    {
        session()->put('google_auth_intent', 'signup');
        session()->put(
            'google_signup_origin',
            $request->query('source') === 'students' ? 'students' : 'login'
        );

        return Socialite::driver('google')->redirect();
    }

    /**
     * Google sends the user back here after they approve (or cancel).
     */
    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $exception) {
            // Cancelled consent, expired state, or a Google error. Log it and send the user back.
            report($exception);
            $request->session()->forget('google_auth_intent');

            return $this->backToLogin('Google sign-in was cancelled or failed. Please try again.');
        }

        $intent = $request->session()->pull('google_auth_intent');
        $signupOrigin = $request->session()->pull('google_signup_origin', 'login');
        $email = $googleUser->getEmail();
        $googleId = $googleUser->getId();

        if ($intent !== 'signup') {
            return $this->backToLogin('Please start sign-up from the registration button.');
        }

        // Refuse only if Google explicitly says the email is not verified.
        $emailIsVerified = $googleUser->user['email_verified'] ?? true;

        if (empty($email) || $emailIsVerified === false) {
            return $this->backToLogin('Your Google email could not be verified.');
        }

        $directoryEntry = DB::table('users_directory')
            ->whereRaw('LOWER(email) = ?', [Str::lower(trim($email))])
            ->first();

        if ($directoryEntry !== null) {
            if ($signupOrigin === 'students') {
                return redirect()
                    ->route('students.index')
                    ->with('add_student_error', 'This Google email is already registered. Use a different account to add a student.');
            }

            return $this->backToLogin('This Google email is already registered. Please sign in.');
        }

        session()->put('pending_registration', [
            'email' => $email,
            'google_id' => $googleId,
            'name' => $googleUser->getName() ?: Str::before($email, '@'),
            'expires_at' => now()->addMinutes(10)->timestamp,
        ]);

        return redirect('/login');
    }

    private function backToLogin(string $message): RedirectResponse
    {
        return redirect('/login')->with('google_error', $message);
    }
}
