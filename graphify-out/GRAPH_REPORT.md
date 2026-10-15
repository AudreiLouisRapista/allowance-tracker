# Graph Report - allowance-tracker  (2026-10-15)

## Corpus Check
- cluster-only mode — file stats not available

## Summary
- 881 nodes · 1853 edges · 82 communities (36 shown, 46 thin omitted)
- Extraction: 99% EXTRACTED · 1% INFERRED · 0% AMBIGUOUS · INFERRED: 12 edges (avg confidence: 0.85)
- Token cost: 36,228 input · 1,944 output

## Graph Freshness
- Built from commit: `84f34e8f`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Settings & Profile UI
- App Layout & Navigation
- Frontend Dependencies
- Sheet & Dialog Components
- App Header & Navigation
- JWT Authentication
- Package Configuration
- Inertia & Fortify Setup
- Password & Profile Validation
- Theme & Toast Notifications
- OAuth & Auth Controllers
- Dropdown Menu Components
- Alert & Login Components
- Card & Auth Layouts
- UI Component Configuration
- Form Input Components
- Sidebar Navigation
- Service Providers
- TypeScript Configuration
- User Model & Auth
- Profile Controller
- Build & Test Scripts
- User Table Migration
- Composer Configuration
- Dev Dependencies
- Navigation & Mobile Hooks
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

## God Nodes (most connected - your core abstractions)
1. `cn()` - 123 edges
2. `react` - 42 edges
3. `AppHeader()` - 30 edges
4. `@inertiajs/react` - 29 edges
5. `Button()` - 23 edges
6. `lucide-react` - 20 edges
7. `User` - 19 edges
8. `DeleteUser()` - 15 edges
9. `SidebarMenuButton()` - 15 edges
10. `AppSidebar()` - 14 edges

## Surprising Connections (you probably didn't know these)
- `DialogHeader()` --calls--> `cn()`  [EXTRACTED]
  resources/js/components/ui/dialog.tsx → resources/js/lib/utils.ts
- `BreadcrumbEllipsis()` --calls--> `cn()`  [EXTRACTED]
  resources/js/components/ui/breadcrumb.tsx → resources/js/lib/utils.ts
- `DropdownMenuCheckboxItem()` --calls--> `cn()`  [EXTRACTED]
  resources/js/components/ui/dropdown-menu.tsx → resources/js/lib/utils.ts
- `DropdownMenuRadioItem()` --calls--> `cn()`  [EXTRACTED]
  resources/js/components/ui/dropdown-menu.tsx → resources/js/lib/utils.ts
- `DropdownMenuShortcut()` --calls--> `cn()`  [EXTRACTED]
  resources/js/components/ui/dropdown-menu.tsx → resources/js/lib/utils.ts

## Import Cycles
- None detected.

## Communities (82 total, 46 thin omitted)

### Community 0 - "Settings & Profile UI"
Cohesion: 0.07
Nodes (44): @inertiajs/react, react, withApp(), DeleteUser(), Heading(), InputError(), PasswordInput(), Props (+36 more)

### Community 1 - "App Layout & Navigation"
Cohesion: 0.13
Nodes (22): AppContent(), Props, AppShell(), Props, AppSidebarHeader(), Breadcrumbs(), Breadcrumb(), BreadcrumbEllipsis() (+14 more)

### Community 2 - "Frontend Dependencies"
Cohesion: 0.06
Nodes (33): dependencies, class-variance-authority, clsx, concurrently, @inertiajs/react, @inertiajs/vite, laravel-vite-plugin, lucide-react (+25 more)

### Community 3 - "Sheet & Dialog Components"
Cohesion: 0.15
Nodes (26): @radix-ui/react-dialog, Sheet(), SheetContent(), SheetDescription(), SheetFooter(), SheetHeader(), SheetOverlay(), SheetPortal() (+18 more)

### Community 4 - "App Header & Navigation"
Cohesion: 0.14
Nodes (24): @radix-ui/react-navigation-menu, AppHeader(), mainNavItems, Props, rightNavItems, AppLogo(), AppLogoIcon(), Avatar() (+16 more)

### Community 5 - "JWT Authentication"
Cohesion: 0.15
Nodes (5): AuthenticateJwt, HandleAppearance, JwtService, TenantConnection, TenantRegistrationService

### Community 6 - "Package Configuration"
Cohesion: 0.07
Nodes (27): private, $schema, type, babel-plugin-react-compiler, clsx, concurrently, @laravel/multiplex, lightningcss-linux-x64-gnu (+19 more)

### Community 7 - "Inertia & Fortify Setup"
Cohesion: 0.11
Nodes (9): HandleInertiaRequests, {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#1}(), {closure#2}() (+1 more)

### Community 8 - "Password & Profile Validation"
Cohesion: 0.13
Nodes (6): PasswordValidationRules, ProfileValidationRules, PasswordUpdateRequest, ProfileDeleteRequest, ProfileUpdateRequest, TwoFactorAuthenticationRequest

### Community 9 - "Theme & Toast Notifications"
Cohesion: 0.16
Nodes (20): sonner, AppearanceToggleTab(), Toaster(), Appearance, applyTheme(), getStoredAppearance(), handleSystemThemeChange(), initializeTheme() (+12 more)

### Community 10 - "OAuth & Auth Controllers"
Cohesion: 0.17
Nodes (5): GoogleAuthController, LogoutController, RegistrationController, Controller, SecurityController

### Community 11 - "Dropdown Menu Components"
Cohesion: 0.14
Nodes (14): @radix-ui/react-dropdown-menu, DropdownMenuCheckboxItem(), DropdownMenuGroup(), DropdownMenuItem(), DropdownMenuLabel(), DropdownMenuRadioItem(), DropdownMenuSeparator(), DropdownMenuShortcut() (+6 more)

### Community 12 - "Alert & Login Components"
Cohesion: 0.19
Nodes (12): AlertError(), Alert(), AlertDescription(), AlertTitle(), alertVariants, PlaceholderPattern(), PlaceholderPatternProps, Login() (+4 more)

### Community 13 - "Card & Auth Layouts"
Cohesion: 0.19
Nodes (11): Card(), CardContent(), CardDescription(), CardFooter(), CardHeader(), CardTitle(), AuthCardLayout(), AuthSimpleLayout() (+3 more)

### Community 14 - "UI Component Configuration"
Cohesion: 0.11
Nodes (17): aliases, components, hooks, lib, ui, utils, iconLibrary, rsc (+9 more)

### Community 16 - "Form Input Components"
Cohesion: 0.12
Nodes (11): lucide-react, @radix-ui/react-checkbox, Checkbox(), IconProps, SelectContent(), SelectItem(), SelectLabel(), SelectScrollDownButton() (+3 more)

### Community 17 - "Sidebar Navigation"
Cohesion: 0.28
Nodes (14): AppSidebar(), footerNavItems, mainNavItems, NavFooter(), NavMain(), SidebarContent(), SidebarFooter(), SidebarGroup() (+6 more)

### Community 18 - "Service Providers"
Cohesion: 0.17
Nodes (3): AppServiceProvider, {closure#1}(), FortifyServiceProvider

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

### Community 27 - "Navigation & Mobile Hooks"
Cohesion: 0.30
Nodes (10): NavUser(), DropdownMenu(), DropdownMenuContent(), DropdownMenuTrigger(), SidebarRail(), useSidebar(), getServerSnapshot(), isSmallerThanBreakpoint() (+2 more)

### Community 28 - "PHP Dependencies"
Cohesion: 0.20
Nodes (10): require, firebase/php-jwt, inertiajs/inertia-laravel, laravel/chisel, laravel/fortify, laravel/framework, laravel/socialite, laravel/tinker (+2 more)

### Community 30 - "Toggle Components"
Cohesion: 0.31
Nodes (7): @radix-ui/react-toggle, @radix-ui/react-toggle-group, ToggleGroup(), ToggleGroupContext, ToggleGroupItem(), Toggle(), toggleVariants

### Community 34 - "Optional Dependencies"
Cohesion: 0.25
Nodes (8): optionalDependencies, @laravel/multiplex, lightningcss-linux-x64-gnu, lightningcss-win32-x64-msvc, @rollup/rollup-linux-x64-gnu, @rollup/rollup-win32-x64-msvc, @tailwindcss/oxide-linux-x64-gnu, @tailwindcss/oxide-win32-x64-msvc

### Community 35 - "Vite Configuration"
Cohesion: 0.25
Nodes (7): @inertiajs/vite, laravel-vite-plugin, @laravel/vite-plugin-wayfinder, @rolldown/plugin-babel, @tailwindcss/vite, vite-plus, @vitejs/plugin-react

### Community 36 - "Auth Types & Inertia Config"
Cohesion: 0.32
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
Cohesion: 0.50
Nodes (4): class-variance-authority, @radix-ui/react-slot, Badge(), badgeVariants

### Community 53 - "Clipboard Hook"
Cohesion: 0.40
Nodes (3): CopiedValue, CopyFn, UseClipboardReturn

## Knowledge Gaps
- **17 isolated node(s):** `babel-plugin-react-compiler`, `concurrently`, `@laravel/multiplex`, `lightningcss-linux-x64-gnu`, `lightningcss-win32-x64-msvc` (+12 more)
  These have ≤1 connection - possible missing edges. (Counts symbols only; 352 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **46 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `cn()` connect `Sheet & Dialog Components` to `Settings & Profile UI`, `App Layout & Navigation`, `App Header & Navigation`, `Theme & Toast Notifications`, `Dropdown Menu Components`, `Alert & Login Components`, `Card & Auth Layouts`, `Form Input Components`, `Sidebar Navigation`, `Badge Component`, `Navigation & Mobile Hooks`, `Toggle Components`?**
  _High betweenness centrality (0.054) - this node is a cross-community bridge._
- **What connects `babel-plugin-react-compiler`, `concurrently`, `@laravel/multiplex` to the rest of the system?**
  _17 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Settings & Profile UI` be split into smaller, more focused modules?**
  _Cohesion score 0.07405063291139241 - nodes in this community are weakly interconnected._
- **Why does `react` connect `Settings & Profile UI` to `App Layout & Navigation`, `Sheet & Dialog Components`, `App Header & Navigation`, `Package Configuration`, `Theme & Toast Notifications`, `Dropdown Menu Components`, `Alert & Login Components`, `Card & Auth Layouts`, `Form Input Components`, `Sidebar Navigation`, `Badge Component`, `Clipboard Hook`, `Navigation & Mobile Hooks`, `Toggle Components`?**
  _High betweenness centrality (0.052) - this node is a cross-community bridge._
- **Should `App Layout & Navigation` be split into smaller, more focused modules?**
  _Cohesion score 0.13174603174603175 - nodes in this community are weakly interconnected._
- **Why does `@inertiajs/react` connect `Settings & Profile UI` to `App Layout & Navigation`, `App Header & Navigation`, `Package Configuration`, `Theme & Toast Notifications`, `Dropdown Menu Components`, `Alert & Login Components`, `Card & Auth Layouts`, `Sidebar Navigation`, `Navigation & Mobile Hooks`?**
  _High betweenness centrality (0.038) - this node is a cross-community bridge._
- **Should `Frontend Dependencies` be split into smaller, more focused modules?**
  _Cohesion score 0.06060606060606061 - nodes in this community are weakly interconnected._