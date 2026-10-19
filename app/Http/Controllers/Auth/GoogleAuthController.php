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

        return $this->signInToFamily(
            $request,
            (int) $directoryEntry->family_id,
            $email,
            $jwtService,
            $tenantConnection,
        );
    }

    /**
     * Turn a verified email into a signed-in session for its family.
     */
    private function signInToFamily(
        Request $request,
        int $familyId,
        string $email,
        JwtService $jwtService,
        TenantConnection $tenantConnection,
    ): RedirectResponse {
        $tenantConnection->switchToFamily($familyId);

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

        $token = $jwtService->createToken($user->id, $familyId, $user->role);
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

    public function redirectToGoogleLogin(Request $request): SymfonyRedirectResponse
    {
        $request->session()->forget(['google_auth_intent', 'google_signup_origin']);
        $request->session()->put('google_auth_intent', 'login');

        return Socialite::driver('google')->redirect();
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

            // Read the intent before clearing it, so a cancelled "add member"
            // attempt returns to the family page instead of the login page.
            $intent = $request->session()->get('google_auth_intent');

            $request->session()->forget([
                'google_auth_intent',
                'google_signup_origin',
                'google_member_family_id',
                'google_member_role',
                'google_member_parent_id',
            ]);

            if ($intent === 'add_member') {
                return to_route('students.index')
                    ->with('add_member_error', 'Google sign-in was cancelled or failed. Please try again.');
            }

            return $this->backToLogin('Google sign-in was cancelled or failed. Please try again.');
        }

        $intent = $request->session()->pull('google_auth_intent');
        $signupOrigin = $request->session()->pull('google_signup_origin', 'login');

        // Stored by FamilyMemberController before the Google redirect.
        $memberFamilyId = $request->session()->pull('google_member_family_id');
        $memberRole = $request->session()->pull('google_member_role');
        $memberParentId = $request->session()->pull('google_member_parent_id');

        $email = $googleUser->getEmail();
        $googleId = $googleUser->getId();

        // Refuse only if Google explicitly says the email is not verified.
        $emailIsVerified = $googleUser->user['email_verified'] ?? true;

        if (empty($email) || $emailIsVerified === false) {
            return $intent === 'add_member'
                ? to_route('students.index')->with('add_member_error', 'Your Google email could not be verified.')
                : $this->backToLogin('Your Google email could not be verified.');
        }

        // Normalize and validate what Google returned.
        $email = Str::lower(trim($email));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $intent === 'add_member'
                ? to_route('students.index')->with('add_member_error', 'Your Google email is not valid.')
                : $this->backToLogin('Your Google email is not valid.');
        }

        if (! is_string($googleId) || trim($googleId) === '') {
            return $intent === 'add_member'
                ? to_route('students.index')->with('add_member_error', 'The Google account could not be verified.')
                : $this->backToLogin('The Google account could not be verified.');
        }

        $googleId = trim($googleId);
        // Fall back to the part before @ when Google does not return a name.
        $name = $googleUser->getName() ?: Str::before($email, '@');

        if ($intent === 'login') {
            $directoryEntry = DB::table('users_directory')
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();

            if ($directoryEntry === null) {
                // Unknown email: hold the details and let the login page ask
                // whether a new family account should be created.
                $request->session()->put('pending_registration', [
                    'email' => $email,
                    'google_id' => $googleId,
                    'name' => $name,
                    'expires_at' => now()->addMinutes(10)->timestamp,
                ]);

                return redirect('/login');
            }

            return $this->signInToFamily(
                $request,
                (int) $directoryEntry->family_id,
                $email,
                $jwtService,
                $tenantConnection,
            );
        }

        if ($intent === 'add_member') {
            // The family id and role must be valid before anything is stored.
            if (
                ! is_numeric($memberFamilyId)
                || (int) $memberFamilyId < 1
                || ! in_array($memberRole, ['parent', 'student'], true)
            ) {
                return to_route('students.index')
                    ->with('add_member_error', 'Please start again from the family members page.');
            }

            // The email must not already be registered to any family.
            if (DB::table('users_directory')->whereRaw('LOWER(email) = ?', [$email])->exists()) {
                return to_route('students.index')
                    ->with('add_member_error', 'This Google email is already registered.');
            }

            // Hold the verified details only. Nothing is written until the parent
            // confirms in the dialog (POST /family-members/register).
            $request->session()->put('pending_member', [
                'email' => $email,
                'google_id' => $googleId,
                'name' => $name,
                'family_id' => (int) $memberFamilyId,
                'role' => $memberRole,
                'parent_id' => $memberParentId !== null ? (int) $memberParentId : null,
                'expires_at' => now()->addMinutes(10)->timestamp,
            ]);

            return to_route('students.index');
        }

        if ($intent !== 'signup') {
            return $this->backToLogin('Please start sign-up from the registration button.');
        }

        $directoryEntry = DB::table('users_directory')
            ->whereRaw('LOWER(email) = ?', [$email])
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
            'name' => $name,
            'expires_at' => now()->addMinutes(10)->timestamp,
        ]);

        return redirect('/login');
    }

    private function backToLogin(string $message): RedirectResponse
    {
        return redirect('/login')->with('google_error', $message);
    }
}
