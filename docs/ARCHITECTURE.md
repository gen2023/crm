# CRM — Architecture

Status: APPROVED
Version: 1.0
Date: 2026-09-03

This document records the long-lived architectural decisions of the project. It changes rarely and only through an explicit decision recorded in `DECISIONS.md`. It does not describe feature scope (see `PHASE-1-SPEC.md`) or day-to-day implementation progress (see `IMPLEMENTATION-LOG.md`).

## Stack

- Framework: Laravel 13 (current major version)
- PHP: 8.3+
- Database: MySQL 8.0
- Templating: Blade
- Roles & Permissions: `spatie/laravel-permission`
- Web server: Apache (mod_php) behind Docker

## Repository layout

- Git root: `/cms`. This is also the Laravel application root — `composer.json`, `app/`, `vendor/`, `.env` all live here.
- `cms/public/` is Laravel's standard `public/` folder and the **only** web-exposed directory (Apache `DocumentRoot=/var/www/public`, via the bind mount `./cms:/var/www` in the outer Docker setup). No application source, vendor code, or `.env` is ever reachable through the web root.
- The outer `crm/` directory (one level above `cms/`) holds Docker/infrastructure files (`docker-compose.yml`, `docker/`) and is **not** part of the application's git repository. Docker is external infrastructure: it is made compatible with what Laravel needs (PHP version, extensions, document root), and it does not dictate application architecture.

## Layering: Controller → Service → Eloquent Model

- No Repository/DAO layer. Eloquent models are used directly by Services as the data-access layer.
- **Controllers** are thin: validate via a `FormRequest`, call one `Service` method, return a response/redirect/view. No business logic, no direct multi-step Eloquent orchestration.
- **FormRequest** classes own input validation (`required`, `email`, `unique`, `confirmed`, `min`/`max`, password policy rules) and coarse "can this user attempt this at all" authorization via `authorize()`.
- **Service** classes own business logic: user creation/editing/deactivation, role assignment, self-protection rules (can't deactivate/delete self, can't strip the last administrator), password-reset orchestration, and calls into `AuditLogger`.
- **Model** (Eloquent) holds data structure, relationships, casts, and simple query scopes — not application business rules.
- **Policy** classes own object/resource-level authorization ("can this admin remove this specific role from this specific user without leaving the system with zero admins"). Coarse permission checks (`users.view`, `users.edit`, ...) always go through Laravel's Gate backed by `spatie/laravel-permission` — never through `if ($user->role === 'admin')`-style checks.

## Modules

- Business modules live under `app/Modules/<Name>/{Controllers,Services,Requests,Policies,routes.php}`.
- No module package (e.g. `nwidart/laravel-modules`) is used. Modules are a plain folder convention on top of standard Laravel.
- `routes/web.php` auto-includes every `app/Modules/*/routes.php` it finds (via `glob()`). Each module's `routes.php` defines its own middleware (`auth`, `can:<permission>`) and route names/prefixes.
- Migrations, views, and tests stay in Laravel's standard locations (`database/migrations`, `resources/views`, `tests/`) rather than nested inside module folders.
- **Future Expansion Contract:** adding a future business module (`Customers`, `Products`, `Orders`, `Integrations`, `Reports`) requires only: its `app/Modules/<Name>/` folder, its migration(s), its `routes.php` (auto-discovered), its permission slugs added to the seeder, and a sidebar entry gated by `@can`. No change to `Core`/`Providers`/`routes/web.php` is required, and no existing module needs to change.

## Authentication

- No Breeze/Jetstream/Fortify/UI scaffolding. A thin custom `AuthController` + `AuthService` wrap Laravel's native `Auth` facade (`Auth::attempt`, session regeneration on login via `$request->session()->regenerate()`, `Auth::logout()` + session invalidation on logout).
- Password reset uses Laravel's native `Password` broker (tokens are hashed, expiring, single-use out of the box) wrapped by a thin `PasswordResetService`. No custom token generation/storage logic.
- No Sanctum/Passport until an actual API consumer exists (see "Protected decisions" below).

## Roles & Permissions

- `spatie/laravel-permission` provides the schema (`roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`) and the `HasRoles` trait on `User`.
- All permission checks go through Gate: `$user->can('users.view')`, `->middleware('can:users.view')`, `@can('users.view')` in Blade. Permissions are seeded data, never hardcoded role-name branches.

## Audit Log

- Custom minimal implementation: `audit_logs` table (`user_id`, `action`, `subject_type`, `subject_id`, `properties` JSON, `created_at`) plus `App\Support\AuditLogger`, called explicitly from Services at the points that matter (user created/updated/deactivated, role changed). No `spatie/laravel-activitylog` dependency.

## Error handling & security baseline

- Laravel's own exception handler renders dedicated views for 401/403/404/422/500; stack traces are hidden whenever `APP_DEBUG=false`.
- CSRF: Laravel's built-in `VerifyCsrfToken` (automatic in the `web` middleware group).
- Rate limiting: Laravel's built-in `throttle:` middleware / `RateLimiter::for()`, applied to login, forgot-password, reset-password.
- Sessions: Laravel's native session configuration (`config/session.php`) — HttpOnly, Secure under HTTPS, SameSite, regenerated on login.

## Versioning policy

- Initial implementation targets **Laravel 13** on **PHP 8.3+**.
- Minor/patch Laravel updates within the current major version are routine maintenance and should happen regularly.
- A Laravel **major**-version upgrade is a separate, dedicated maintenance task — it is never bundled silently with feature work.
- Before a major upgrade: read the official upgrade guide, verify PHP version compatibility, verify Composer dependency compatibility, and run the full test suite on the current version first.
- After a major upgrade: separately re-verify authentication, authorization, migrations, Docker build, and critical CRM functionality before resuming feature work.

## Protected decisions

The following require a new, explicit entry in `DECISIONS.md` before they can change — they must never change silently as a side effect of feature work:

- Framework choice (Laravel)
- Laravel major version
- Introducing a Repository layer
- Introducing a module package
- Adding a new major architectural layer
- Installing additional Composer packages beyond what is currently approved
- Changing the module folder structure/convention
- Changing the database architecture
- Changing the authentication architecture
- Git root / repository layout (`/cms` as both git root and Laravel root)
