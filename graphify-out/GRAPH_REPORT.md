# Graph Report - allowance-tracker  (2026-10-22)

## Corpus Check
- 183 files · ~44,467 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 22 file(s) not represented in the graph (top: (none) 16, .example 1, .ini 1)

## Summary
- 1097 nodes · 2403 edges · 81 communities (39 shown, 42 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 43 edges (avg confidence: 0.91)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `bf7d8554`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- react
- App Layout & Navigation
- Frontend Dependencies
- Sheet & Dialog Components
- cn
- Illuminate\Http\Request
- Package Configuration
- Inertia & Fortify Setup
- Password & Profile Validation
- Family members, roles, and sidebar guide
- Illuminate\Http\RedirectResponse
- Dropdown Menu Components
- TenantConnection
- allowance-plans.tsx
- UI Component Configuration
- use-current-url.ts
- Sidebar Navigation
- AllowancePlan
- TypeScript Configuration
- User Model & Auth
- Illuminate\Http\Request
- Build & Test Scripts
- User Table Migration
- Composer Configuration
- Dev Dependencies
- nav-user.tsx
- PHP Dependencies
- User Factory
- Toggle Components
- Testing Setup
- Security Tests
- Optional Dependencies
- Vite Configuration
- 3. Findings
- Pest Plugin Configuration
- Build Scripts
- use-appearance.tsx
- Frontend Dev Dependencies
- Controllers/FamilyMemberController.php
- Autoload Configuration
- Laravel Extra Config
- Cache Table Migration
- Purchase Request Status Migration
- Users Table Migration
- Database Seeder
- 2026_10_20_000000_add_created_by_to_users_table.php
- Collapsible Component
- Illuminate\Database\Migrations\Migration
- Families Table Migration
- Users Directory Migration
- User Status Migration
- User Role Migration
- Allowance Plan Status Migration
- Illuminate\Support\Facades\Schema
- Allowance Payouts Migration
- Student Balance Migration
- Request Items Migration
- Request Reviews Migration
- Request Funds Migration
- Notifications Migration
- user-info.tsx
- Login and registration change guide

## God Nodes (most connected - your core abstractions)
1. `cn()` - 125 edges
2. `react` - 48 edges
3. `@inertiajs/react` - 34 edges
4. `TenantConnection` - 32 edges
5. `AppHeader()` - 31 edges
6. `Button()` - 29 edges
7. `AllowancePlans()` - 26 edges
8. `lucide-react` - 25 edges
9. `User` - 19 edges
10. `FamilyMembers()` - 19 edges

## Surprising Connections (you probably didn't know these)
- ``app/Http/Controllers/Auth/FamilyMemberController.php`` --references--> `GoogleAuthController`  [INFERRED]
  docs/FAMILY-MEMBERS-ROLES-AND-SIDEBAR-GUIDE.md → app/Http/Controllers/Auth/GoogleAuthController.php
- `H-04 — Logout does not revoke the JWT (High)` --references--> `AuthenticateJwt`  [INFERRED]
  docs/SECURITY-REVIEW-2026-10-22.md → app/Http/Middleware/AuthenticateJwt.php
- `10. Common questions and gotchas` --references--> `TenantMemberService`  [INFERRED]
  docs/FAMILY-MEMBERS-ROLES-AND-SIDEBAR-GUIDE.md → app/Services/TenantMemberService.php
- `10. Common questions and gotchas` --references--> `TenantRegistrationService`  [INFERRED]
  docs/FAMILY-MEMBERS-ROLES-AND-SIDEBAR-GUIDE.md → app/Services/TenantRegistrationService.php
- `L-01 — Duplicate/legacy settings routes and a broken destroy method (Low)` --references--> `Auth`  [INFERRED]
  docs/SECURITY-REVIEW-2026-10-22.md → resources/js/types/auth.ts

## Import Cycles
- None detected.

## Communities (81 total, 42 thin omitted)

### Community 0 - "react"
Cohesion: 0.05
Nodes (50): @inertiajs/react, lucide-react, react, AlertError(), Heading(), InputError(), PasswordInput(), Props (+42 more)

### Community 1 - "App Layout & Navigation"
Cohesion: 0.10
Nodes (26): AppContent(), Props, AppShell(), Props, AppSidebarHeader(), Breadcrumbs(), Breadcrumb(), BreadcrumbEllipsis() (+18 more)

### Community 2 - "Frontend Dependencies"
Cohesion: 0.06
Nodes (33): dependencies, class-variance-authority, clsx, concurrently, @inertiajs/react, @inertiajs/vite, laravel-vite-plugin, lucide-react (+25 more)

### Community 3 - "Sheet & Dialog Components"
Cohesion: 0.19
Nodes (19): AppHeader(), Props, rightNavItems, AppLogo(), AppLogoIcon(), Sheet(), SheetContent(), SheetDescription() (+11 more)

### Community 4 - "cn"
Cohesion: 0.13
Nodes (26): @radix-ui/react-navigation-menu, Input(), NavigationMenu(), NavigationMenuContent(), NavigationMenuIndicator(), NavigationMenuItem(), NavigationMenuLink(), NavigationMenuList() (+18 more)

### Community 5 - "Illuminate\Http\Request"
Cohesion: 0.19
Nodes (5): AuthenticateJwt, EnsureRole, HandleAppearance, JwtService, H-04 — Logout does not revoke the JWT (High)

### Community 6 - "Package Configuration"
Cohesion: 0.07
Nodes (27): private, $schema, type, babel-plugin-react-compiler, clsx, concurrently, @laravel/multiplex, lightningcss-linux-x64-gnu (+19 more)

### Community 7 - "Inertia & Fortify Setup"
Cohesion: 0.11
Nodes (4): HandleInertiaRequests, {closure#1}(), {closure#2}(), {closure#3}()

### Community 8 - "Password & Profile Validation"
Cohesion: 0.07
Nodes (9): ResetUserPassword, PasswordValidationRules, ProfileValidationRules, PasswordUpdateRequest, ProfileUpdateRequest, TwoFactorAuthenticationRequest, AppServiceProvider, {closure#1}() (+1 more)

### Community 9 - "Family members, roles, and sidebar guide"
Cohesion: 0.06
Nodes (36): 10. Common questions and gotchas, 11. Glossary, 12. Change checklist, 1. The one-sentence summary, 3. The data at a glance, 4. The flow: adding a family member, 5. Why each file exists, 6. Validation and security layers (+28 more)

### Community 10 - "Illuminate\Http\RedirectResponse"
Cohesion: 0.12
Nodes (7): FamilyMemberController, GoogleAuthController, LogoutController, RegistrationController, Controller, SecurityController, C-01 — Passwordless email login permits account takeover (Critical)

### Community 11 - "Dropdown Menu Components"
Cohesion: 0.15
Nodes (14): @radix-ui/react-dropdown-menu, DropdownMenuCheckboxItem(), DropdownMenuGroup(), DropdownMenuItem(), DropdownMenuLabel(), DropdownMenuRadioItem(), DropdownMenuSeparator(), DropdownMenuShortcut() (+6 more)

### Community 12 - "TenantConnection"
Cohesion: 0.14
Nodes (6): MigrateTenants, TenantConnection, TenantMemberService, TenantRegistrationService, 2. The big analogy: an apartment building, H-01 — Manual member creation bypasses the master directory (High)

### Community 13 - "allowance-plans.tsx"
Cohesion: 0.08
Nodes (50): @radix-ui/react-select, @radix-ui/react-slot, NewAccountDialog(), Badge(), badgeVariants, Card(), CardContent(), CardDescription() (+42 more)

### Community 14 - "UI Component Configuration"
Cohesion: 0.11
Nodes (17): aliases, components, hooks, lib, ui, utils, iconLibrary, rsc (+9 more)

### Community 16 - "use-current-url.ts"
Cohesion: 0.40
Nodes (4): IsCurrentOrParentUrlFn, IsCurrentUrlFn, UseCurrentUrlReturn, WhenCurrentUrlFn

### Community 17 - "Sidebar Navigation"
Cohesion: 0.26
Nodes (16): AppSidebar(), footerNavItems, parentNavItems, studentNavItems, NavFooter(), NavMain(), SidebarContent(), SidebarFooter() (+8 more)

### Community 18 - "AllowancePlan"
Cohesion: 0.07
Nodes (10): AllowancePlanController, {closure#1}(), {closure#2}(), AllowancePayout, AllowancePlan, AllowancePlanStatus, FamilyMember, UserRole (+2 more)

### Community 19 - "TypeScript Configuration"
Cohesion: 0.12
Nodes (15): compilerOptions, allowJs, esModuleInterop, forceConsistentCasingInFileNames, isolatedModules, jsx, module, moduleResolution (+7 more)

### Community 21 - "Illuminate\Http\Request"
Cohesion: 0.14
Nodes (7): ProfileController, {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), `app/Http/Controllers/Auth/FamilyMemberController.php`

### Community 22 - "Build & Test Scripts"
Cohesion: 0.15
Nodes (13): scripts, ci:check, dev, lint, lint:check, post-autoload-dump, post-create-project-cmd, post-root-package-install (+5 more)

### Community 23 - "User Table Migration"
Cohesion: 0.23
Nodes (6): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#3}()

### Community 24 - "Composer Configuration"
Cohesion: 0.17
Nodes (11): autoload-dev, psr-4, description, keywords, license, minimum-stability, name, prefer-stable (+3 more)

### Community 25 - "Dev Dependencies"
Cohesion: 0.17
Nodes (12): require-dev, fakerphp/faker, larastan/larastan, laravel/boost, laravel/pail, laravel/pao, laravel/pint, laravel/sail (+4 more)

### Community 27 - "nav-user.tsx"
Cohesion: 0.30
Nodes (10): NavUser(), DropdownMenu(), DropdownMenuContent(), DropdownMenuTrigger(), SidebarRail(), useSidebar(), getServerSnapshot(), isSmallerThanBreakpoint() (+2 more)

### Community 28 - "PHP Dependencies"
Cohesion: 0.20
Nodes (10): require, firebase/php-jwt, inertiajs/inertia-laravel, laravel/chisel, laravel/fortify, laravel/framework, laravel/socialite, laravel/tinker (+2 more)

### Community 30 - "Toggle Components"
Cohesion: 0.29
Nodes (8): class-variance-authority, @radix-ui/react-toggle, @radix-ui/react-toggle-group, ToggleGroup(), ToggleGroupContext, ToggleGroupItem(), Toggle(), toggleVariants

### Community 34 - "Optional Dependencies"
Cohesion: 0.25
Nodes (8): optionalDependencies, @laravel/multiplex, lightningcss-linux-x64-gnu, lightningcss-win32-x64-msvc, @rollup/rollup-linux-x64-gnu, @rollup/rollup-win32-x64-msvc, @tailwindcss/oxide-linux-x64-gnu, @tailwindcss/oxide-win32-x64-msvc

### Community 35 - "Vite Configuration"
Cohesion: 0.25
Nodes (7): @inertiajs/vite, laravel-vite-plugin, @laravel/vite-plugin-wayfinder, @rolldown/plugin-babel, @tailwindcss/vite, vite-plus, @vitejs/plugin-react

### Community 36 - "3. Findings"
Cohesion: 0.06
Nodes (31): 1. Executive summary, 2. Severity definitions, 3. Findings, 4. Positive controls verified, 5. Prioritized remediation plan, 6. Verification checklist after fixes, 7. Limitations, 8. Appendix — commands and inputs (+23 more)

### Community 37 - "Pest Plugin Configuration"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 38 - "Build Scripts"
Cohesion: 0.29
Nodes (7): scripts, build, build:ssr, check, check:fix, dev, types:check

### Community 39 - "use-appearance.tsx"
Cohesion: 0.15
Nodes (22): sonner, withApp(), AppearanceToggleTab(), Toaster(), TooltipProvider(), Appearance, applyTheme(), getStoredAppearance() (+14 more)

### Community 40 - "Frontend Dev Dependencies"
Cohesion: 0.33
Nodes (6): devDependencies, babel-plugin-react-compiler, @laravel/vite-plugin-wayfinder, @rolldown/plugin-babel, @types/node, vite-plus

### Community 42 - "Controllers/FamilyMemberController.php"
Cohesion: 0.32
Nodes (4): {closure#1}(), {closure#2}(), {closure#3}(), FamilyMemberController

### Community 43 - "Autoload Configuration"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 44 - "Laravel Extra Config"
Cohesion: 0.40
Nodes (5): extra, laravel, post-create-project, dont-discover, installer

### Community 69 - "user-info.tsx"
Cohesion: 0.30
Nodes (8): @radix-ui/react-avatar, Avatar(), AvatarFallback(), AvatarImage(), UserInfo(), getInitial(), GetInitialsFn, useInitials()

### Community 83 - "Login and registration change guide"
Cohesion: 0.22
Nodes (8): 2a. Set the OAuth intent before redirecting to Google, 2b. Branch on the intent after verifying the Google email, Intended behavior, Login and registration change guide, Step 1: Add a separate sign-up route, Step 2: Track the chosen action and branch in the Google callback, Step 3: Add the sign-up option to the login page, Step 4: Reuse registration creation and verify both paths

## Knowledge Gaps
- **17 isolated node(s):** `@types/react`, `@types/react-dom`, `concurrently`, `react-dom`, `tailwindcss` (+12 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 433 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **42 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `3. Findings` connect `3. Findings` to `Family members, roles, and sidebar guide`, `Illuminate\Http\RedirectResponse`, `TenantConnection`, `Illuminate\Http\Request`?**
  _High betweenness centrality (0.338) - this node is a cross-community bridge._
- **What connects `@types/react`, `@types/react-dom`, `concurrently` to the rest of the system?**
  _17 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `react` be split into smaller, more focused modules?**
  _Cohesion score 0.05218365061590145 - nodes in this community are weakly interconnected._
- **Why does `Auth` connect `3. Findings` to `react`?**
  _High betweenness centrality (0.335) - this node is a cross-community bridge._
- **Should `App Layout & Navigation` be split into smaller, more focused modules?**
  _Cohesion score 0.10042283298097252 - nodes in this community are weakly interconnected._
- **Should `Frontend Dependencies` be split into smaller, more focused modules?**
  _Cohesion score 0.06060606060606061 - nodes in this community are weakly interconnected._
- **Should `cn` be split into smaller, more focused modules?**
  _Cohesion score 0.13446969696969696 - nodes in this community are weakly interconnected._