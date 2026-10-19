# Graph Report - allowance-tracker  (2026-10-20)

## Corpus Check
- 174 files · ~36,920 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 22 file(s) not represented in the graph (top: (none) 16, .example 1, .ini 1)

## Summary
- 995 nodes · 2115 edges · 85 communities (38 shown, 47 thin omitted)
- Extraction: 99% EXTRACTED · 1% INFERRED · 0% AMBIGUOUS · INFERRED: 23 edges (avg confidence: 0.89)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `e9987435`
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
- Card & Auth Layouts
- UI Component Configuration
- utils.ts
- Sidebar Navigation
- Student
- TypeScript Configuration
- User Model & Auth
- Profile Controller
- Build & Test Scripts
- User Table Migration
- Composer Configuration
- Dev Dependencies
- useIsMobile
- PHP Dependencies
- User Factory
- Toggle Components
- Testing Setup
- Security Tests
- Optional Dependencies
- Vite Configuration
- Auth Types & Inertia Config
- Pest Plugin Configuration
- Build Scripts
- Frontend Dev Dependencies
- Password Reset
- Autoload Configuration
- Laravel Extra Config
- Cache Table Migration
- Purchase Request Status Migration
- Users Table Migration
- Database Seeder
- Badge Component
- Collapsible Component
- Clipboard Hook
- Family Status Migration
- Families Table Migration
- Users Directory Migration
- User Status Migration
- User Role Migration
- Allowance Plan Status Migration
- Parent Student Migration
- Allowance Plan Migration
- Allowance Payouts Migration
- Student Balance Migration
- Purchase Requests Migration
- Request Items Migration
- Request Reviews Migration
- Request Funds Migration
- Notifications Migration
- user-info.tsx
- JwtService
- Login and registration change guide
- FamilyMemberController

## God Nodes (most connected - your core abstractions)
1. `cn()` - 123 edges
2. `react` - 48 edges
3. `@inertiajs/react` - 33 edges
4. `AppHeader()` - 31 edges
5. `Button()` - 27 edges
6. `TenantConnection` - 23 edges
7. `lucide-react` - 22 edges
8. `User` - 19 edges
9. `FamilyMembers()` - 17 edges
10. `Input()` - 16 edges

## Surprising Connections (you probably didn't know these)
- ``app/Http/Controllers/Auth/FamilyMemberController.php`` --references--> `GoogleAuthController`  [INFERRED]
  docs/FAMILY-MEMBERS-ROLES-AND-SIDEBAR-GUIDE.md → app/Http/Controllers/Auth/GoogleAuthController.php
- `10. Common questions and gotchas` --references--> `TenantMemberService`  [INFERRED]
  docs/FAMILY-MEMBERS-ROLES-AND-SIDEBAR-GUIDE.md → app/Services/TenantMemberService.php
- `10. Common questions and gotchas` --references--> `TenantRegistrationService`  [INFERRED]
  docs/FAMILY-MEMBERS-ROLES-AND-SIDEBAR-GUIDE.md → app/Services/TenantRegistrationService.php
- `2. The big analogy: an apartment building` --references--> `TenantConnection`  [INFERRED]
  docs/FAMILY-MEMBERS-ROLES-AND-SIDEBAR-GUIDE.md → app/Services/TenantConnection.php
- `2. The big analogy: an apartment building` --references--> `TenantMemberService`  [INFERRED]
  docs/FAMILY-MEMBERS-ROLES-AND-SIDEBAR-GUIDE.md → app/Services/TenantMemberService.php

## Import Cycles
- None detected.

## Communities (85 total, 47 thin omitted)

### Community 0 - "react"
Cohesion: 0.05
Nodes (68): @inertiajs/react, lucide-react, react, AlertError(), Heading(), InputError(), NewAccountDialog(), PasswordInput() (+60 more)

### Community 1 - "App Layout & Navigation"
Cohesion: 0.06
Nodes (43): sonner, withApp(), AppContent(), Props, AppShell(), Props, AppSidebarHeader(), AppearanceToggleTab() (+35 more)

### Community 2 - "Frontend Dependencies"
Cohesion: 0.06
Nodes (33): dependencies, class-variance-authority, clsx, concurrently, @inertiajs/react, @inertiajs/vite, laravel-vite-plugin, lucide-react (+25 more)

### Community 3 - "Sheet & Dialog Components"
Cohesion: 0.21
Nodes (17): AppHeader(), Props, rightNavItems, Sheet(), SheetContent(), SheetDescription(), SheetFooter(), SheetHeader() (+9 more)

### Community 4 - "cn"
Cohesion: 0.14
Nodes (26): @radix-ui/react-navigation-menu, NavigationMenu(), NavigationMenuContent(), NavigationMenuIndicator(), NavigationMenuItem(), NavigationMenuLink(), NavigationMenuList(), NavigationMenuTrigger() (+18 more)

### Community 5 - "Illuminate\Http\Request"
Cohesion: 0.18
Nodes (8): AuthenticateJwt, EnsureRole, HandleAppearance, {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}()

### Community 6 - "Package Configuration"
Cohesion: 0.07
Nodes (28): private, $schema, type, babel-plugin-react-compiler, clsx, concurrently, @laravel/multiplex, lightningcss-linux-x64-gnu (+20 more)

### Community 7 - "Inertia & Fortify Setup"
Cohesion: 0.10
Nodes (4): HandleInertiaRequests, {closure#1}(), {closure#2}(), {closure#3}()

### Community 8 - "Password & Profile Validation"
Cohesion: 0.07
Nodes (8): PasswordValidationRules, ProfileValidationRules, PasswordUpdateRequest, ProfileUpdateRequest, TwoFactorAuthenticationRequest, AppServiceProvider, {closure#1}(), FortifyServiceProvider

### Community 9 - "Family members, roles, and sidebar guide"
Cohesion: 0.06
Nodes (34): 10. Common questions and gotchas, 11. Glossary, 12. Change checklist, 1. The one-sentence summary, 3. The data at a glance, 4. The flow: adding a family member, 5. Why each file exists, 6. Validation and security layers (+26 more)

### Community 10 - "Illuminate\Http\RedirectResponse"
Cohesion: 0.16
Nodes (5): LogoutController, RegistrationController, Controller, SecurityController, StudentController

### Community 11 - "Dropdown Menu Components"
Cohesion: 0.14
Nodes (18): @radix-ui/react-dropdown-menu, NavUser(), DropdownMenu(), DropdownMenuCheckboxItem(), DropdownMenuContent(), DropdownMenuGroup(), DropdownMenuItem(), DropdownMenuLabel() (+10 more)

### Community 12 - "TenantConnection"
Cohesion: 0.24
Nodes (5): TenantConnection, TenantMemberService, TenantRegistrationService, 2. The big analogy: an apartment building, `app/Services/TenantMemberService.php`

### Community 13 - "Card & Auth Layouts"
Cohesion: 0.19
Nodes (13): AppLogo(), AppLogoIcon(), Card(), CardContent(), CardDescription(), CardFooter(), CardHeader(), CardTitle() (+5 more)

### Community 14 - "UI Component Configuration"
Cohesion: 0.11
Nodes (17): aliases, components, hooks, lib, ui, utils, iconLibrary, rsc (+9 more)

### Community 16 - "utils.ts"
Cohesion: 0.18
Nodes (11): @radix-ui/react-checkbox, Checkbox(), Separator(), IsCurrentOrParentUrlFn, IsCurrentUrlFn, useCurrentUrl(), UseCurrentUrlReturn, WhenCurrentUrlFn (+3 more)

### Community 17 - "Sidebar Navigation"
Cohesion: 0.26
Nodes (15): AppSidebar(), footerNavItems, parentNavItems, studentNavItems, NavFooter(), NavMain(), SidebarContent(), SidebarFooter() (+7 more)

### Community 18 - "Student"
Cohesion: 0.23
Nodes (3): Student, UserRole, UserStatus

### Community 19 - "TypeScript Configuration"
Cohesion: 0.12
Nodes (15): compilerOptions, allowJs, esModuleInterop, forceConsistentCasingInFileNames, isolatedModules, jsx, module, moduleResolution (+7 more)

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

### Community 27 - "useIsMobile"
Cohesion: 0.53
Nodes (5): SidebarProvider(), getServerSnapshot(), isSmallerThanBreakpoint(), mediaQueryListener(), useIsMobile()

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

### Community 36 - "Auth Types & Inertia Config"
Cohesion: 0.28
Nodes (6): Auth, User, InertiaConfig, @inertiajs/core, InputHTMLAttributes, react

### Community 37 - "Pest Plugin Configuration"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 38 - "Build Scripts"
Cohesion: 0.29
Nodes (7): scripts, build, build:ssr, check, check:fix, dev, types:check

### Community 40 - "Frontend Dev Dependencies"
Cohesion: 0.33
Nodes (6): devDependencies, babel-plugin-react-compiler, @laravel/vite-plugin-wayfinder, @rolldown/plugin-babel, @types/node, vite-plus

### Community 43 - "Autoload Configuration"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 44 - "Laravel Extra Config"
Cohesion: 0.40
Nodes (5): extra, laravel, post-create-project, dont-discover, installer

### Community 51 - "Badge Component"
Cohesion: 0.67
Nodes (3): @radix-ui/react-slot, Badge(), badgeVariants

### Community 53 - "Clipboard Hook"
Cohesion: 0.40
Nodes (3): CopiedValue, CopyFn, UseClipboardReturn

### Community 69 - "user-info.tsx"
Cohesion: 0.35
Nodes (7): Avatar(), AvatarFallback(), AvatarImage(), UserInfo(), getInitial(), GetInitialsFn, useInitials()

### Community 83 - "Login and registration change guide"
Cohesion: 0.22
Nodes (8): 2a. Set the OAuth intent before redirecting to Google, 2b. Branch on the intent after verifying the Google email, Intended behavior, Login and registration change guide, Step 1: Add a separate sign-up route, Step 2: Track the chosen action and branch in the Google callback, Step 3: Add the sign-up option to the login page, Step 4: Reuse registration creation and verify both paths

## Knowledge Gaps
- **17 isolated node(s):** `@types/react`, `@types/react-dom`, `concurrently`, `react-dom`, `tailwindcss` (+12 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 403 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **47 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `react` connect `react` to `App Layout & Navigation`, `Sheet & Dialog Components`, `cn`, `user-info.tsx`, `Package Configuration`, `Dropdown Menu Components`, `Card & Auth Layouts`, `utils.ts`, `Sidebar Navigation`, `Badge Component`, `Clipboard Hook`, `useIsMobile`, `Toggle Components`?**
  _High betweenness centrality (0.048) - this node is a cross-community bridge._
- **What connects `@types/react`, `@types/react-dom`, `concurrently` to the rest of the system?**
  _17 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `react` be split into smaller, more focused modules?**
  _Cohesion score 0.0513590391908976 - nodes in this community are weakly interconnected._
- **Why does `cn()` connect `cn` to `react`, `App Layout & Navigation`, `Sheet & Dialog Components`, `user-info.tsx`, `Dropdown Menu Components`, `Card & Auth Layouts`, `utils.ts`, `Sidebar Navigation`, `Badge Component`, `useIsMobile`, `Toggle Components`?**
  _High betweenness centrality (0.043) - this node is a cross-community bridge._
- **Should `App Layout & Navigation` be split into smaller, more focused modules?**
  _Cohesion score 0.06498015873015874 - nodes in this community are weakly interconnected._
- **Why does `@inertiajs/react` connect `react` to `App Layout & Navigation`, `Sheet & Dialog Components`, `Package Configuration`, `Dropdown Menu Components`, `Card & Auth Layouts`, `utils.ts`, `Sidebar Navigation`?**
  _High betweenness centrality (0.035) - this node is a cross-community bridge._
- **Should `Frontend Dependencies` be split into smaller, more focused modules?**
  _Cohesion score 0.06060606060606061 - nodes in this community are weakly interconnected._