# Family members, roles, and sidebar guide

This document explains the changes made in this session:

1. Adding a parent or student to an **existing family** through Google.
2. Showing every active family member (not only students).
3. Moving the Family Members page buttons to the right and adding hover feedback.
4. Giving parents and students **different sidebar menus**.
5. Why each piece of code exists, with a plain-language analogy.

Line numbers are approximate and may move as the code changes.

---

## 1. The one-sentence summary

A parent can now invite another Google account into their **existing family database** and choose whether that account is a **parent** or a **student**; the sidebar then shows each role only the pages that role should see.

---

## 2. The big analogy: an apartment building

Think of the application as one apartment building.

| Application concept | Apartment analogy |
| --- | --- |
| Master database | The lobby directory that knows which family lives in which unit. |
| `families` table | The list of apartment units. Each row has a `database_name`. |
| One family database (`family_<id>`) | The inside of one apartment unit. Each family has its own furniture (users, roles, students). |
| `users_directory` | The building-wide guest list. One email can appear only once in the whole building. |
| `TenantConnection` | The elevator key. It chooses which apartment door the next query opens. |
| `TenantRegistrationService` | The leasing office. It creates a **brand-new apartment** and its first tenant. |
| `TenantMemberService` | The front desk inside an existing apartment. It adds a new roommate **without building a new apartment**. |
| `parent_student` | The guest list inside the apartment: which parent is responsible for which student. |
| Google OAuth | The ID checker at the entrance. It confirms the person is who they say they are. |
| JWT cookie | The wristband that proves the person already passed the ID check. |
| Middleware (`jwt.auth`, `role:parent`) | The security guard at the elevator. No wristband, or the wrong colour wristband, means no entry. |
| Controller | The receptionist. It takes the request and sends it to the right clerk. |
| Service | The back-office clerk. It does the real work and enforces the business rules. |
| Inertia page | The TV screen in the lobby that shows the result. |

The most important idea: **a co-parent is a new person inside the same apartment, not a new apartment.** That is why this flow never runs `CREATE DATABASE`.

---

## 3. The data at a glance

```text
MASTER DATABASE
┌──────────────────────────────────────────────────────┐
│ families                                             │
│  id | family_name | database_name | ...              │
├──────────────────────────────────────────────────────┤
│ users_directory                                      │
│  id | family_id | email   (email is UNIQUE)          │
└──────────────────────────────────────────────────────┘
                    │ family_id points to
                    ▼
FAMILY DATABASE (family_<id>)
┌──────────────────────────────────────────────────────┐
│ users                                                │
│  id | name | email | google_id | is_creator         │
│  usr_status_id -> user_status(status)                │
│  usr_role_id   -> user_role(role)                    │
├──────────────────────────────────────────────────────┤
│ parent_student                                       │
│  parent_id  -> users.id                              │
│  student_id -> users.id                              │
│  UNIQUE(parent_id, student_id)                       │
└──────────────────────────────────────────────────────┘
```

- `users_directory` answers: **"Which family owns this email?"**
- `users` answers: **"Who is inside this family?"**
- `parent_student` answers: **"Which parent is linked to which student?"**

---

## 4. The flow: adding a family member

### Step 1 — The parent picks a role

The dialog in `resources/js/pages/parent/familyMembers.tsx` has a role dropdown (`parent` or `student`). The choice is sent as a query string:

```text
/family-members/add?role=parent
```

Why a simple link instead of a form? Because the next step is a **full-page redirect to Google**. The browser leaves the React page, so a normal anchor is the right tool.

### Step 2 — The server remembers the request

`FamilyMemberController@redirectToGoogle` validates the role, then stores the intent **in the server-side session**:

```php
$request->session()->put('google_auth_intent', 'add_member');
$request->session()->put('google_member_family_id', $request->attributes->get('auth_family_id'));
$request->session()->put('google_member_role', $validated['role']);
$request->session()->put('google_member_parent_id', $request->attributes->get('auth_user')->id);
```

The family id comes from the **JWT**, not from the URL. This is the claim-ticket analogy: the browser can show a ticket, but the server decides what is written on it. A malicious user cannot change `family_id` to another family.

### Step 3 — Google returns

`GoogleAuthController@handleGoogleCallback` reads the saved intent. If it is `add_member`, it:

1. Checks the email and Google id from Google.
2. Refuses an email that is already in `users_directory`.
3. Puts the verified details into `pending_member` for **10 minutes**.
4. Redirects back to the Family Members page.

Nothing is written to the database yet. This is the **holding room** analogy: the visitor has shown a valid ID, but has not been given a room key until the parent confirms.

### Step 4 — The parent confirms

The confirmation dialog reads `pendingMember` from the shared Inertia props. It shows the email and the chosen role. The parent can:

- **Cancel** → `POST /family-members/register/cancel` forgets the pending data and creates nothing.
- **Add member** → `POST /family-members/register` runs the real creation.

The pending data is server-held, so refreshing the page does not lose it, and the browser cannot edit the role or family id in the meantime.

### Step 5 — The service creates the member

`TenantMemberService@addMember` is the back-office clerk:

1. Validates the role, family id, email, Google id, and name.
2. Rejects an email already in `users_directory`.
3. Switches the tenant connection to the **existing** family database.
4. Looks up the `active` status and the chosen role.
5. Rejects duplicate email or Google id **inside** that family.
6. For a student, verifies the adding parent is an active parent in the same family.
7. Inserts the user and the `parent_student` links in a transaction.
8. Inserts the `users_directory` row **last**.

### Step 6 — Linking rules

| New role | Links created |
| --- | --- |
| `student` | One row: the new student → the parent who added them. |
| `parent` | One row for the new parent → **every active student** in this family. |

Why link a new parent to every active student? So the co-parent can immediately see and manage the same students. Otherwise the new parent would exist but appear disconnected.

---

## 5. Why each file exists

### `app/Http/Controllers/Auth/FamilyMemberController.php`

**Job:** the receptionist for "add a member".

- `redirectToGoogle()` – validates the role and saves the intent/family/role/parent in the session before leaving for Google.
- `store()` – reads `pending_member`, validates its shape and expiry, checks same-family ownership, calls the service, and returns a success or error message.
- `cancel()` – forgets the pending data.

**Why a separate controller?** The existing `GoogleAuthController` already handles **sign-in** and **new-family sign-up**. Adding a third flow there would make one class do three different jobs. A separate controller keeps each flow readable.

### `app/Http/Controllers/Auth/GoogleAuthController.php`

**Job:** the shared Google callback.

The callback is one URL, but three things can start it: sign-in, sign-up, and add-member. It branches on `google_auth_intent`:

```php
if ($intent === 'add_member') {
    // hold the verified details and return to the family page
}
```

**Why hold instead of create immediately?** The parent must confirm the account first. Creating during the callback would make an accidental click or a mistyped Google account permanent.

The method also normalizes and validates what Google returned (`email`, `google_id`, `name`). Never trust a third-party response blindly.

### `app/Services/TenantMemberService.php`

**Job:** the business rule engine for adding a member.

It is a service, not a controller, because the rules are reusable and testable on their own. The controller should not know how to pick a tenant connection or which pivot rows to write.

Key decisions:

- **Role whitelist** (`parent`, `student`): stops unexpected values.
- **Switch to the existing family** (`switchToFamily`): no `CREATE DATABASE`.
- **Lookup rows must exist**: the status and role ids come from the family database.
- **Duplicate checks at two levels**: inside the family and across the building (`users_directory`).
- **Transaction for the tenant writes**: the user and pivot rows succeed or fail together.
- **Directory insert last**: its unique email index is the final safety net. If it fails, the catch block deletes the tenant user and pivot rows, because the master and tenant databases **cannot share one transaction**.

Why does the order matter? If the directory row were written first and the tenant insert failed, the building directory would point to a person who does not exist. Writing it last means a failure leaves no half-registered person.

### `routes/web.php`

**Job:** the building signs.

All member routes sit inside:

```php
Route::middleware(['jwt.auth', 'role:parent'])->group(...)
```

- `jwt.auth` proves the user is signed in.
- `role:parent` proves the user is a parent.

So even if a student typed `/students` or `/family-members/add` manually, they would be stopped before reaching the controller.

Routes added:

| Method | URL | Name | Purpose |
| --- | --- | --- | --- |
| GET | `/family-members/add` | `family-members.add` | Start Google for the new member. |
| POST | `/family-members/register` | `family-members.register` | Confirm and create. |
| POST | `/family-members/register/cancel` | `family-members.cancel` | Throw away the pending data. |

### `app/Http/Middleware/HandleInertiaRequests.php`

**Job:** the intercom between Laravel and React.

It shares:

- `flash.add_member_error` – why adding failed.
- `flash.add_member_success` – what was created.
- `pendingMember` – the email and role waiting for confirmation.

`pendingMember` reads the session, so the confirmation dialog survives a page refresh. It only returns data while the 10-minute window is still valid.

### `app/Http/Controllers/StudentController.php`

**Job:** the family list, edits, archives, and restores.

The important change is that the page now lists **every active member**, not only students:

```php
$familyMember = Student::active()
    ->with(['status:id,status', 'role:id,role'])
    ->orderBy('name')
    ->get(['id', 'name', 'email', 'phonenumber', 'usr_status_id', 'usr_role_id']);
```

Why include `usr_status_id` and `usr_role_id` in `get()`? Because those are the foreign keys for `with()`. If they are missing, Laravel cannot match the related rows and the relations come back empty. This is the classic "I loaded the relation but it is null" mistake.

The table now also shows a **Role** column, so a parent can see who is a parent and who is a student.

### `resources/js/pages/parent/familyMembers.tsx`

**Job:** the screen the parent interacts with.

It contains:

- The member table with Name, Email, Phone, Status, Role, and Action.
- The **role dropdown** for the new account.
- The **confirmation dialog** for `pendingMember`.
- Success and error messages from the flash props.

Why `pendingMember` comes from props instead of local state? The Google redirect is a full page load, so React state is lost. The server session is the only place that survives the round trip.

### `resources/js/components/app-sidebar.tsx`

**Job:** the navigation menu.

This file now defines two separate menus:

```tsx
const parentNavItems: NavItem[] = [
    { title: 'Dashboard', href: '/dashboard', icon: LayoutGrid },
    { title: 'Family members', href: '/students', icon: Users },
    { title: 'Archives', href: '/archives', icon: Archive },
];

const studentNavItems: NavItem[] = [
    { title: 'Dashboard', href: '/student/dashboard', icon: LayoutGrid },
];
```

Then it picks one based on the signed-in role:

```tsx
const mainNavItems: NavItem[] =
    auth.user.role === 'student' ? studentNavItems : parentNavItems;
```

**Why this is safe:** the sidebar is only a **map**. Hiding a link is not security. The real lock is the `role:parent` middleware on the server. The menu simply stops showing doors that role cannot open, which is better UX.

**Analogy:** parents get the **manager's key ring** (dashboard, family members, archives). Students get a **resident's key** (their own dashboard). The building's locks still decide what each key can open.

---

## 6. Validation and security layers

Think of airport security. One check is never enough.

| Layer | What it stops | Example |
| --- | --- | --- |
| UI | Accidental bad input | Dropdown only offers parent/student. |
| Route middleware | Wrong role | A student cannot open parent routes. |
| Controller | Tampered or expired flow | Pending data older than 10 minutes is rejected. |
| Service | Business-rule violations | Duplicate email, missing parent, invalid role. |
| Database | Final duplicate guarantee | `users_directory.email` is unique. |

Specific protections:

- **Role whitelist**: only `parent` and `student`.
- **Server-side family id**: taken from the JWT, never from the URL.
- **Same-family check**: the confirming parent's family must match the pending family.
- **Active-parent check**: a new student must be linked to a real active parent in the same tenant.
- **Expiry**: `pending_member` lasts 10 minutes.
- **No half-created accounts**: tenant transaction plus compensating cleanup if the directory insert fails.
- **No JWT for the new member**: the original parent stays signed in; the new account signs in later with Google.

---

## 7. The Family Members page UI changes

The top of the page now looks like this:

```tsx
<div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <h1 className="text-xl font-semibold">Family members</h1>
    <div className="flex flex-wrap items-center gap-2 sm:justify-end">
        ...
    </div>
</div>
```

What each class does:

| Class | Meaning |
| --- | --- |
| `flex flex-col` | Stack the heading and buttons on small screens. |
| `sm:flex-row` | Put them side by side from the `sm` breakpoint up. |
| `sm:items-center` | Vertically centre the heading and buttons. |
| `sm:justify-between` | Push the heading left and the buttons right. |
| `flex-wrap` | Let buttons wrap instead of overflowing on narrow screens. |
| `sm:justify-end` | Align the buttons to the right on larger screens. |
| `w-full sm:w-auto` | Full-width buttons on mobile, normal width on desktop. |

Hover effects:

```tsx
className="transition-all hover:-translate-y-0.5 hover:shadow-md"
```

- `transition-all` animates the change.
- `hover:-translate-y-0.5` lifts the button slightly.
- `hover:shadow-md` adds a soft shadow.

The table rows use `transition-colors hover:bg-muted/50` so the row under the mouse is easy to follow. The Action column is right-aligned.

### The `asChild` fix

Before:

```tsx
<Button>
    <Link href="/archives">Show Archives</Link>
</Button>
```

This renders a link **inside** a button, which is invalid HTML. Now:

```tsx
<Button asChild>
    <Link href="/archives">Show Archives</Link>
</Button>
```

`asChild` tells the button component to pass its styles to the link instead of wrapping it. The result is one valid element.

### Flash message duration (5000 ms)

Every flash message now auto-dismisses after **5 seconds**.

- `resources/js/hooks/use-auto-dismiss.ts` – shared timer hook with a 5000 ms default.
- `resources/js/pages/auth/login.tsx` – the Google sign-in error alert.
- `resources/js/pages/parent/familyMembers.tsx` – the add-member error and success messages, including the copy inside the dialog.
- `resources/js/components/ui/sonner.tsx` – `duration={5000}`, so every toast uses the same lifetime.
- The parent and student dashboards already used 5000 ms for the welcome alert.

**Analogy:** a lobby announcement is read once, then the screen clears so the next message can be seen. The hook owns the timer and the cleanup, so every page behaves the same way.

---

## 8. The role-based sidebar

### Parent menu

| Item | URL | Why |
| --- | --- | --- |
| Dashboard | `/dashboard` | Parent overview. |
| Family members | `/students` | Add, edit, and archive members. |
| Archives | `/archives` | Restore removed members. |

### Student menu

| Item | URL | Why |
| --- | --- | --- |
| Dashboard | `/student/dashboard` | The student's own area. |

### Why students do not see Family members or Archives

Those pages are protected by `role:parent`. Showing the links would only lead to a 403. Hiding them keeps the interface honest.

### Adding a student menu item later

When a new student page is created (for example, an allowance request page), add it to `studentNavItems` and protect its route with `role:student`. Do both:

1. Server route inside the `role:student` group.
2. Sidebar item in `studentNavItems`.

The first is security; the second is usability.

---

## 9. How to test manually

### Parent flow

1. Sign in as a parent.
2. Open **Family members**.
3. Click **Add Family member**.
4. Choose **Parent**, then **Sign up with Google**.
5. Approve a Google account that is not yet registered.
6. Confirm **Add member**.
7. Check that the new parent appears in the table with the role `parent`.
8. Sign in as the new parent and confirm they see the same students.

### Student flow

1. Repeat the flow but choose **Student**.
2. Confirm the new student appears with the role `student`.
3. Sign in as that student.
4. Confirm the sidebar shows only **Dashboard** and that `/students` returns 403.

### Error cases

- Use an email already in `users_directory` → error, nothing created.
- Cancel the Google consent screen → return to the family page with an error.
- Leave the confirmation dialog open for more than 10 minutes → the pending data expires.

### Automatic checks

```text
npm run types:check
npm run build
php -l app/Services/TenantMemberService.php
php -l app/Http/Controllers/Auth/FamilyMemberController.php
php artisan route:list --path=family-members
```

---

## 10. Common questions and gotchas

**Does this create a new database?**
No. `TenantMemberService` calls `switchToFamily($familyId)` and writes into the existing `family_<id>` database. Only `TenantRegistrationService` creates databases.

**Why is the original parent still signed in after adding a member?**
The add-member flow never issues a JWT for the new person. It only creates rows. The new person signs in separately with their own Google account.

**What if the directory insert fails after the tenant insert succeeds?**
The service deletes the tenant user and pivot rows. This is manual compensation because the two databases are separate connections.

**Why lower-case emails?**
`Str::lower(trim($email))` makes `Juan@Example.com` and `juan@example.com` the same identity. Otherwise duplicates slip through.

**Why show the role in the table?**
Because the page now lists parents too, and the role is the fastest way to tell them apart.

**Why is the sidebar not enough security?**
Because anyone can type a URL. The server-side middleware is the lock; the sidebar is only the sign on the door.

---

## 11. Glossary

| Term | Meaning |
| --- | --- |
| **Tenant** | One family and its own database. |
| **Master database** | The shared database with `families` and `users_directory`. |
| **Tenant database** | `family_<id>`, containing that family's users and links. |
| **JWT** | The signed cookie that proves who is signed in. |
| **Middleware** | Code that runs before a request reaches a controller; used here for authentication and role checks. |
| **Inertia** | The bridge that lets Laravel return React pages and shared props. |
| **Pivot table** | A table that links two other tables; here `parent_student`. |
| **Compensation** | Manually undoing a successful write when a later step fails. |
| **Flash message** | A one-time message stored for the next request. |
| **Pending data** | Verified Google details held in the session until the parent confirms. |

---

## 12. Change checklist

- [x] Add co-parent/co-student flow without a new database.
- [x] Save the role and family on the server before Google.
- [x] Hold verified Google details for confirmation.
- [x] Validate at UI, route, controller, service, and database levels.
- [x] Link new students to the adding parent and new parents to all active students.
- [x] Insert the directory row last and clean up on failure.
- [x] Show all active members with their role.
- [x] Move page actions to the right and add hover feedback.
- [x] Fix the invalid link-inside-button markup with `asChild`.
- [x] Auto-dismiss every flash message and toast after 5000 ms.
- [x] Give parents and students separate sidebar menus.
- [x] Protect member routes with `jwt.auth` and `role:parent`.
