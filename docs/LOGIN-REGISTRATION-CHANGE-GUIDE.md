# Login and registration change guide

This guide shows the current code and proposed code for separating Google sign-in and sign-up. It is documentation only; the application code has not been changed.

**Line numbers below refer to the current files.** They will shift after code is added. The current login page uses Google only; it does not have an email/password field.

## Intended behavior

- **Sign in with Google:** allow sign-in only when the verified Google email exists in `users_directory`. Use its `family_id` to select the tenant database and validate the user there. Do not create an account for an unknown sign-in email.
- **Sign-up with Google:** if the verified email is new, hold the Google details in the existing pending-registration session and ask the user to confirm. If it is already registered, tell the user to sign in.
- Use the existing table name **`users_directory`** (plural). The current table maps an email to a family; it is not named `user_directory`.

## Step 1: Add a separate sign-up route

**File:** `routes/web.php`

**Before — current lines 9–10:**

```php
Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);
```

**After — add the sign-up route between these routes:**

```php
Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/signup', [GoogleAuthController::class, 'redirectToGoogleSignup']);
Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);
```

## Step 2: Track the chosen action and branch in the Google callback

**File:** `app/Http/Controllers/Auth/GoogleAuthController.php`

### 2a. Set the OAuth intent before redirecting to Google

**Before — current lines 18–23:** the existing redirect does not record whether the user chose sign-in or sign-up.

```php
public function redirectToGoogle(): RedirectResponse
{
    return Socialite::driver('google')->redirect();
}
```

**After — replace that method and add the second method:**

```php
public function redirectToGoogle(): RedirectResponse
{
    session()->put('google_auth_intent', 'login');

    return Socialite::driver('google')->redirect();
}

public function redirectToGoogleSignup(): RedirectResponse
{
    session()->put('google_auth_intent', 'signup');

    return Socialite::driver('google')->redirect();
}
```

The intent is stored server-side in the session. Do not accept a database name or `family_id` from the browser.

### 2b. Branch on the intent after verifying the Google email

**Before — current lines 52–67:** the callback treats every unknown email as a possible registration, regardless of which button was used.

```php
$directoryEntry = DB::table('users_directory')->where('email', $email)->first();

if ($directoryEntry === null) {
    session()->put('pending_registration', [
        'email' => $email,
        'google_id' => $googleId,
        'name' => $googleUser->getName() ?: Str::before($email, '@'),
        'expires_at' => now()->addMinutes(10)->timestamp,
    ]);

    return redirect('/login');
}
```

**After — in `handleGoogleCallback()`:**

1. Read and remove the intent at the start of the callback, before the Google request:

   ```php
   $intent = $request->session()->pull('google_auth_intent', 'login');
   ```

2. After the existing Google email verification check, replace the current directory lookup and unknown-email branch with:

   ```php
   $directoryEntry = DB::table('users_directory')
       ->where('email', $email)
       ->first();

   if ($intent === 'login' && $directoryEntry === null) {
       return $this->backToLogin(
           'No account was found for this Google email. Please sign up first.'
       );
   }

   if ($intent === 'signup' && $directoryEntry !== null) {
       return $this->backToLogin(
           'This Google email is already registered. Please sign in.'
       );
   }

   if ($intent === 'signup') {
       session()->put('pending_registration', [
           'email' => $email,
           'google_id' => $googleId,
           'name' => $googleUser->getName() ?: Str::before($email, '@'),
           'expires_at' => now()->addMinutes(10)->timestamp,
       ]);

       return redirect('/login');
   }
   ```

3. Leave the rest of the existing sign-in callback in place. It switches to the tenant using `$directoryEntry->family_id`, checks the tenant user and status, verifies the Google ID, creates the JWT cookie, and redirects to the dashboard (currently beginning at line 68).

## Step 3: Add the sign-up option to the login page

**File:** `resources/js/pages/auth/login.tsx`

**Before — current lines 38–63:** there is one Google button linking to the sign-in route and labeled **Sign in with Google**.

```tsx
<a href="/auth/google/redirect">
    {/* Existing Google SVG icon */}
    Sign in with Google
</a>
```

**After — keep the existing button, then add this below it inside the same button group:**

```tsx
<p>Register with</p>
<Button asChild variant="outline" className="w-full">
    <a href="/auth/google/signup">
        {/* Reuse the existing Google SVG icon from the sign-in button. */}
        Sign-up with Google
    </a>
</Button>
```

In the actual page, copy the existing Google SVG markup into the new button so the sign-in and sign-up choices look consistent. Keep `<NewAccountDialog />` in place; it displays the confirmation for a new pending registration.

## Step 4: Reuse registration creation and verify both paths

The following existing files already support confirmed registration; they should not need duplicate account-creation logic:

- `app/Http/Controllers/Auth/RegistrationController.php` — currently checks the pending registration and expiration (around lines 20–29), then calls the service.
- `app/Services/TenantRegistrationService.php` — creates the family, tenant database, and first parent; it inserts the email into `users_directory` at approximately line 84.
- `app/Services/TenantConnection.php` — resolves a `family_id` to its tenant database.

Add focused tests under `tests/Feature/Auth/` for:

- A registered email signs in to the tenant database identified by its directory `family_id`.
- An unknown sign-in email is rejected and creates no account or directory entry.
- A new sign-up email reaches confirmation; confirming creates one family, user, and directory entry.
- An existing email cannot be duplicated; cancellation and expired pending registration do not create an account.
- Unverified emails, wrong Google IDs, inactive/missing tenant users, and Google cancellation fail safely.
- The page shows **Sign in with Google**, **Register with**, and **Sign-up with Google**, and the buttons use their correct routes.

After implementation, run the focused auth tests and the frontend build.
