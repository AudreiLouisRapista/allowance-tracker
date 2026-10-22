# Security Review — Allowance Tracker

**Date:** 2026-10-22  
**Scope:** `C:\laragon\www\allowance-tracker` (Laravel 13.34.0, Inertia 3, React 19)  
**Revision reviewed:** `bf7d855` plus the uncommitted phase-4 allowance-plan files  
**Review type:** Code review, configuration review, dependency audit  
**Changes made:** None. This document is the only artifact.

---

## 1. Executive summary

The application has a solid multi-tenant foundation: tenant databases are selected from a signed JWT claim, all models used by tenant features use the `tenant` connection, SQL is parameterized, the JWT algorithm is fixed to HS256, the JWT cookie is `HttpOnly`, and CSRF protection is active.

The review found **one critical authentication bypass**: the email sign-in form authenticates a user by email address alone. Anyone who knows a registered family email can obtain a valid JWT for that account, including a family creator. This defeats Google OAuth entirely and is reachable from the public login page.

Four high-severity issues follow: a second account-creation path that bypasses `users_directory` and `google_id`, horizontal editing of parent profiles by co-parents, missing rate limits on public auth endpoints, and JWTs that remain valid after logout. A set of medium and low issues covers cookie transport, security headers, environment defaults, dependency constraints, database privileges, and dead Fortify/settings routes.

**Fix the critical issue before any deployment or demo to real users.**

---

## 2. Severity definitions

| Severity | Meaning |
|---|---|
| Critical | Direct account takeover or full tenant compromise with a simple public request. |
| High | Serious loss of confidentiality/integrity, or a realistic path to it; fix before release. |
| Medium | Exploitable under specific conditions, defense-in-depth gap, or deployment footgun. |
| Low | Hardening, dead code, or a functional defect with limited security impact. |

---

## 3. Findings

### C-01 — Passwordless email login permits account takeover (Critical)

**Locations**

- `routes/web.php:12` — `POST /auth/email/login` is public.
- `app/Http/Controllers/Auth/GoogleAuthController.php:18-49` — `loginWithEmail()`.
- `app/Http/Controllers/Auth/GoogleAuthController.php:52-107` — `signInToFamily()`.
- `resources/js/pages/auth/login.tsx:72` — the form posts only an email.

**Evidence**

`loginWithEmail()` validates an email, looks it up in `users_directory` (`:27-31`), then calls `signInToFamily()`. That method resolves the tenant user, checks only that the status is `active`, and issues a JWT (`:90-96`). There is no password, no Google ID comparison, no emailed proof of ownership, and no rate limit. The JWT carries the user's real `sub`, `family_id`, and `role`, so the caller receives the same session a Google login would produce.

**Impact**

Any visitor who knows or guesses a family member's email can sign in as that user. For a creator account this gives full control of the family: member management, archives, allowance plans, and profile edits. This is a complete bypass of Google authentication.

**Remediation**

1. Remove the email form and the `/auth/email/login` route, and use Google OAuth as the only sign-in path.
2. If email sign-in is a product requirement, send a single-use, time-limited signed link (magic link) and rate-limit both request and verification. Never issue a session from an email address alone.
3. If the route is a development convenience, gate it with `app()->isLocal()` and hide the form in non-local environments. Do not rely on UI hiding alone; enforce it server-side.

---

### H-01 — Manual member creation bypasses the master directory (High)

**Locations**

- `app/Http/Controllers/FamilyMemberController.php:75-90` — manual `store()`.
- `app/Http/Controllers/FamilyMemberController.php:79` — uniqueness checked only in `tenant.users`.
- `app/Http/Controllers/FamilyMemberController.php:83-89` — `FamilyMember::create()` with no `created_by`.
- `app/Services/TenantMemberService.php:125` — the service path sets `created_by`.
- `app/Services/TenantMemberService.php:159` — the service path inserts `users_directory`.

**Evidence**

The manual add flow writes directly to the tenant `users` table. It does not insert a `users_directory` row and does not store a `google_id`. Both login paths resolve users through `users_directory`, so a manually added student can never sign in. Because the directory is not checked, the same email can also be added to a different family, creating duplicate identities. The missing `created_by` also means the creator-link lock enforced by `syncStudentParents()` does not apply.

**Impact**

Orphan, unloginable accounts; cross-family email duplication; broken parent-link invariants. A parent can create records that no one can use and that administrators cannot easily reconcile.

**Remediation**

Route manual creation through `TenantMemberService`, or explicitly insert the directory row, set `created_by`, and validate against `users_directory` before writing. Decide whether manual students are allowed to sign in at all; if not, mark the account as non-login and exclude it from login lookups deliberately.

---

### H-02 — Co-parents can edit any parent, including the creator (High)

**Locations**

- `app/Http/Controllers/FamilyMemberController.php:257-273` — `canAccessMember()`.
- `app/Http/Controllers/FamilyMemberController.php:269` — non-students are always accessible.
- `resources/js/pages/parent/family-member.tsx:263` — Edit is shown for every member.

**Evidence**

`canAccessMember()` returns `true` for any member whose role is not `student`. `update()` uses that check, so a co-parent can change the creator's name and phone number. The UI exposes the same action on every row.

**Impact**

Horizontal data tampering and social-engineering opportunities. No role escalation is possible through this endpoint, but a co-parent can misattribute or alter another parent's profile.

**Remediation**

Allow parent-row edits only for the creator or the member themselves. Keep student edits limited to linked students. Add a feature test that a co-parent receives 403 when updating the creator.

---

### H-03 — Public auth endpoints have no rate limiting (High)

**Locations**

- `routes/web.php:12-18` — email login, Google start/callback, register.
- `app/Http/Controllers/Auth/GoogleAuthController.php:33-35` — distinct "no account" message.
- `app/Providers/FortifyServiceProvider.php:64-70` — limiter applies only to Fortify's `/login`.

**Evidence**

The custom `/auth/email/login` endpoint has no `throttle`. It also returns a different message when the email is unknown, which turns it into an account-enumeration oracle. `/auth/register` and the Google routes are likewise unthrottled, and registration creates a new MySQL database per family.

**Impact**

Email enumeration, credential/identity brute forcing, and resource exhaustion through repeated family creation. The Fortify limiter gives a false sense of protection because it protects an endpoint the UI does not use.

**Remediation**

Apply `throttle:5,1` (or stricter) to email login, keyed by normalized email plus IP. Use one generic failure message. Throttle registration and OAuth callback. Add a per-account/IP cap on family creation and alert on abnormal database growth.

---

### H-04 — Logout does not revoke the JWT (High)

**Locations**

- `app/Http/Controllers/Auth/LogoutController.php:15` — only forgets the cookie.
- `config/jwt.php:8` — default TTL is 720 minutes.
- `app/Services/JwtService.php:26-34` — no `jti`, token version, or denylist.

**Evidence**

Logout expires the browser cookie. A copied or stolen token remains valid until `exp`. There is no server-side revocation, and account archival is the only event that invalidates a token (via the status check in `AuthenticateJwt`).

**Impact**

A stolen token grants up to 12 hours of access, including after the user believes they signed out. Password/phone changes do not invalidate it.

**Remediation**

Add a `token_version` column to tenant users and include it in the JWT; increment it on logout, role change, archive, and credential change. Alternatively, keep a short-lived access token plus a refresh token stored server-side. At minimum, reduce the TTL and document the residual risk.

---

### H-05 — Cookie transport security depends on the request (High)

**Locations**

- `app/Http/Controllers/Auth/GoogleAuthController.php:96` — `secure: $request->isSecure()`.
- `app/Http/Controllers/Auth/RegistrationController.php:61` — same pattern.
- `config/session.php:172` — `secure` comes from `SESSION_SECURE_COOKIE`, which is unset.
- `bootstrap/app.php` — no trusted-proxy configuration.

**Evidence**

If TLS terminates at a reverse proxy and proxies are not trusted, `isSecure()` is false and both the JWT and session cookies are issued without `Secure`. `.env.example` does not set `SESSION_SECURE_COOKIE`.

**Impact**

Cookies can be sent over plain HTTP in a misconfigured production deployment, exposing the JWT and session to network attackers.

**Remediation**

Force `Secure` when `app()->isProduction()` (or read a dedicated config flag). Set `SESSION_SECURE_COOKIE=true` in production. Configure `TrustProxies` for the actual load balancer, and enable HSTS at the edge.

---

### M-01 — Google email verification fails open (Medium)

**Location:** `app/Http/Controllers/Auth/GoogleAuthController.php:176`

```php
$emailIsVerified = $googleUser->user['email_verified'] ?? true;
```

If Google omits the claim, the code treats the email as verified. Require `=== true` and reject a missing or non-boolean claim. This is defense-in-depth against unusual provider responses.

---

### M-02 — No security headers (Medium)

**Locations:** `bootstrap/app.php`, `public/.htaccess`

There is no CSP, HSTS, `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, or `Permissions-Policy`. Add a small middleware (or web-server config) with at least `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, and HSTS in production. Add a CSP once the Vite/Inertia asset origins are known.

---

### M-03 — Unsafe environment template and missing required keys (Medium)

**Locations**

- `.env.example:4` — `APP_DEBUG=true`
- `.env.example:21` — `LOG_LEVEL=debug`
- `.env.example:32` — `SESSION_ENCRYPT=false`
- `.env.example` — no `SESSION_SECURE_COOKIE`, `JWT_SECRET`, `JWT_TTL_MINUTES`, or `GOOGLE_*` entries.

`composer setup` copies this file. A deployment that follows the template will ship debug error pages and debug logs, and a fresh install will fail until JWT/Google values are added by hand.

**Remediation**

Create a production-safe `.env.example` (`APP_DEBUG=false`, `LOG_LEVEL=warning`, `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`) and a separate `.env.local.example` for development. Document every required secret and fail fast at boot when one is missing.

---

### M-04 — Unpinned security-sensitive Composer dependencies (Medium)

**Location:** `composer.json:13,18`

```json
"firebase/php-jwt": "*",
"laravel/socialite": "*"
```

Installed versions are `firebase/php-jwt` 7.2.1 and `laravel/socialite` 5.31.0. The lock file pins them today, but a future `composer update` can jump major versions without review. Pin to compatible major ranges (for example `^7.2` and `^5.31`), commit the lock file, and use Dependabot/Renovate with review.

---

### M-05 — Shared root database credentials (Medium)

**Location:** `config/database.php:69-71` (master MySQL) and `:89-91` (tenant)

Both connections default to `root` with an empty password and read the same `DB_USERNAME`/`DB_PASSWORD`. Any SQL injection or compromised tenant code would have master-database access, and a production deployment that forgets `DB_PASSWORD` silently runs as root.

**Remediation**

Use separate least-privilege MySQL users: the master user gets only the master schema; the tenant user gets only per-tenant schemas (or create one user per tenant). Never use `root`. Fail startup when credentials are missing in production.

---

### M-06 — Vulnerable JavaScript build tooling (Medium)

`npm audit` on 2026-10-22 reports:

| Package | Severity | Notes |
|---|---|---|
| `concurrently@10.0.5` via `shell-quote` | Critical | Fix available |
| `vite@8.3.2` | High | Fix available |
| `vite-plus@0.3.0` (Vitest/`@vitest/mocker`/`tinypool`/`oxfmt`) | Moderate | Fix requires `vite-plus@1.3.0` (semver-major) |

These are build/development tools, not runtime browser dependencies, but they run on developer machines and CI. `concurrently`, `vite`, and TypeScript are currently listed under `dependencies`, so `npm audit --omit=dev` still reports them.

**Remediation**

Run `npm audit fix` for `concurrently` and `vite`, move build-only packages to `devDependencies`, and plan the `vite-plus` 1.3.0 upgrade. Add `npm audit --audit-level=high` to CI. Treat the Vitest advisories as lower priority because production bundles do not include the test runner.

---

### M-07 — Unlimited tenant database creation (Medium)

**Location:** `app/Services/TenantRegistrationService.php:48`, called from `RegistrationController@store`.

Each new family runs `CREATE DATABASE` and a full tenant migration. A single Google account can repeat the flow; there is no per-user or per-IP cap, quota, or alerting.

**Remediation**

Add a registration rate limit and a per-account/IP family cap. Monitor database count and migration failures. Consider a manual approval step for new families if abuse becomes likely.

---

### M-08 — Unused session/Fortify auth surface remains enabled (Medium)

**Locations**

- `config/fortify.php:146-147` — password reset and email verification features.
- `database/seeders/DatabaseSeeder.php:20-23` — seeds `test@example.com` with the factory password `password`.
- `routes/settings.php` — `settings/security` and `settings/password` still use the session guard.

The app's real routes use `jwt.auth`, so a Fortify session login cannot read family data. However, the extra `/login` POST, password-reset, verification, and session-protected settings endpoints are live, and the seeder creates a known credential pair if `db:seed` is ever run in production.

**Remediation**

Disable the unused Fortify features/routes or remove Fortify entirely. Remove the default seeded user and guard `db:seed` against production. Delete or re-implement the session-only settings routes.

---

### L-01 — Duplicate/legacy settings routes and a broken destroy method (Low)

**Locations**

- `routes/settings.php` — `profile.edit`, `profile.update`, and `appearance.edit` are registered twice; the JWT group wins.
- `app/Http/Controllers/Settings/ProfileController.php:68` — `destroy(ProfileDeleteRequest $request)` references an undefined class and uses `Auth::logout()` without importing `Auth`.

The security page is unreachable with JWT auth, and profile deletion would fatal if it were reached. Clean up the duplicated routes and either implement or remove `destroy()`.

---

### L-02 — JWT/session hardening gaps (Low)

- `app/Services/JwtService.php:25` sets `iss` but `decodeToken()` does not verify it; there is no `aud` or `jti`.
- No `session()->regenerate()` after login (`GoogleAuthController.php:87`, `RegistrationController.php:49`).
- `SESSION_ENCRYPT=false` means session payloads (pending emails and roles) are stored in plaintext in the session store.

Validate `iss`/`aud`, rotate the session ID on login, and enable session encryption in production.

---

## 4. Positive controls verified

- `composer audit` — no advisories.
- All raw SQL uses bindings (`whereRaw(..., [$email])`); no string interpolation of user input. The dynamic `CREATE DATABASE` name is built from a generated integer id.
- All tenant models set `$connection = TenantConnection::CONNECTION`; controllers re-select the tenant from the signed JWT claim (`AuthenticateJwt.php:30-58`), not from request input.
- JWT algorithm is fixed to `HS256`, and the secret is 64 characters in the reviewed `.env`.
- JWT cookie is `HttpOnly`, `SameSite=Lax`, and `Secure` when the request is HTTPS.
- CSRF protection is active on the `web` middleware group; Inertia sends the XSRF token.
- `users_directory.email` has a unique constraint, which is the final duplicate guard on the Google/member-service path.
- Allowance-plan authorization is enforced server-side (`AllowancePlanController.php:141-172`) and is tenant-scoped.
- No committed `.env`, keys, or credentials; no `dangerouslySetInnerHTML`; no hardcoded secrets found in `app/`, `config/`, `routes/`, or `resources/js/`.

---

## 5. Prioritized remediation plan

### Immediate (before any real-user deployment)

1. C-01: remove or replace passwordless email login.
2. H-03: rate-limit and genericize all public auth endpoints.
3. H-05: force secure cookies in production and configure trusted proxies.

### Next

4. H-01: fix manual member creation (directory + `created_by`).
5. H-02: restrict parent-row edits to the creator or self.
6. H-04: add JWT revocation/token versioning.
7. M-01: require `email_verified === true`.

### Hardening

8. M-02: security headers.
9. M-03: production-safe environment template.
10. M-04: pin Composer constraints.
11. M-05: least-privilege database users.
12. M-06: upgrade JavaScript tooling and move build packages to `devDependencies`.
13. M-07: registration quotas.
14. M-08/L-01/L-02: remove dead Fortify/settings surface and finish JWT/session hardening.

### CI and ongoing checks

- `composer audit` and `npm audit --audit-level=high` on every push.
- Secret scanning and a PHP SAST pass.
- Regression tests for cross-tenant access, auth bypass, and role boundaries.
- A deployment checklist asserting `APP_DEBUG=false`, `APP_ENV=production`, `SESSION_SECURE_COOKIE=true`, and non-root database users.

---

## 6. Verification checklist after fixes

- [ ] A request to sign in with a known email but no Google proof is rejected.
- [ ] More than five failed email attempts from one IP/email return HTTP 429.
- [ ] A co-parent updating the creator receives HTTP 403.
- [ ] A manually added member either appears in `users_directory` and can sign in, or is explicitly marked non-login.
- [ ] A token copied before logout is rejected after logout.
- [ ] Production `Set-Cookie` includes `Secure`, `HttpOnly`, and `SameSite`.
- [ ] `curl -I` shows the chosen security headers.
- [ ] Cross-tenant reads/writes return 403/404, never another family's data.

---

## 7. Limitations

- MySQL/Laragon was not running during this review, so no live requests were executed against tenant data. Findings are based on code and configuration review.
- There is no tenant feature-test suite; the existing Pest baseline has unrelated starter-kit failures. Security tests should be added alongside the fixes.
- Dependency results are a point-in-time snapshot of `composer.lock` and `package-lock.json` on 2026-10-22.
- The uncommitted phase-4 allowance-plan files were included in the review; commit hashes in this report refer to `bf7d855`.

---

## 8. Appendix — commands and inputs

```text
composer audit
npm audit
npm audit --omit=dev
php artisan route:list --except-vendor
php artisan route:list -v --path=login
php artisan route:list -v --path=settings
```

Reviewed areas: `app/Http/Controllers`, `app/Http/Middleware`, `app/Services`, `app/Models`, `routes/`, `config/`, `.env.example`, `bootstrap/app.php`, `database/migrations`, `database/seeders`, `resources/js/pages/auth`, `resources/js/pages/parent`, `composer.json`, `package.json`.
