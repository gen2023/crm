# CRM — Implementation Log

Working log of implementation steps. Each entry records verifiable technical facts and outcomes only — no internal reasoning/chain-of-thought.

Entry template:

```text
## Step N — Title

Date: YYYY-MM-DD

Goal:
...

Changed files:
...

Dependencies:
...

Checks:
...

Tests:
...

Docker:
...

Problems:
...

Resolution:
...

Status:
DONE / BLOCKED

Next step:
...
```

---

## Step 1 — Laravel skeleton + Docker integration

Date: 2026-09-03

Goal:
Bring up a clean, unmodified Laravel 13 application inside the existing Docker/Apache/MySQL setup. No Auth/Users/Roles/Permissions/Audit/Dashboard/CRM business logic.

Changed files:
- `crm/docker/app/Dockerfile` — `WORKDIR` fixed from the nonexistent `/var/public` to `/var/www` (the actual bind-mounted app path); added `COPY --from=composer:2 /usr/bin/composer /usr/bin/composer`; added `ENTRYPOINT ["/bin/bash", "/docker/entrypoint.sh"]`.
- `crm/docker/app/scripts/entrypoint.sh` — added conditional `composer install` (if `vendor/` missing) and `.env` bootstrap (`cp .env.example .env` + `artisan key:generate`, if `.env` missing); converted CRLF line endings to LF.
- `crm/docker/app/scripts/wait-for.sh` — converted CRLF line endings to LF (no logic change).
- `cms/.env`, `cms/.env.example` — `APP_URL` set to `https://localhost`; `DB_CONNECTION`/`DB_HOST`/`DB_PORT`/`DB_DATABASE`/`DB_USERNAME`/`DB_PASSWORD` switched from the installer's default SQLite to the project's MySQL service (`db` / `cmsdb` / `user` / `pass`, matching `docker-compose.yml`).

Created files:
- `cms/` — full Laravel 13.30.1 application skeleton (`app/`, `bootstrap/`, `config/`, `database/migrations/` [`create_users_table` incl. `password_reset_tokens` and `sessions`, `create_cache_table`, `create_jobs_table`], `public/` [`index.php`, `.htaccess`, `robots.txt`, `favicon.ico`], `resources/`, `routes/`, `storage/`, `tests/`, `artisan`, `composer.json`/`composer.lock`, `phpunit.xml`, `package.json`, `vite.config.js`, `.gitignore`, `.editorconfig`, `README.md`, `AGENTS.md`, `CLAUDE.md`) — unmodified Laravel defaults except where listed above.
- `cms/docs/ARCHITECTURE.md`, `cms/docs/PHASE-1-SPEC.md`, `cms/docs/DECISIONS.md`, `cms/docs/IMPLEMENTATION-LOG.md` (this file) — created prior to Step 1's code changes, per the project owner's governance requirement.
- `crm/.gitattributes` — forces LF line endings for `*.sh`, to prevent the CRLF regression described below on future checkouts (Windows `core.autocrlf=true`).

Deleted files:
- None in the repositories. (Scratch directories used during scaffolding, `/tmp/laravel-new` and `/tmp/skeleton` inside the app container, were removed after use — not part of any repository.)

Dependencies (from the generated `cms/composer.json`):
- Require: `php ^8.3`, `laravel/framework ^13.17` (installed: 13.30.1), `laravel/tinker ^3.0`.
- Require-dev: `fakerphp/faker`, `laravel/pail`, `laravel/pao`, `laravel/pint`, `mockery/mockery`, `nunomaduro/collision`, `phpunit/phpunit`.
- No additional packages (Spatie, etc.) were installed — out of scope for Step 1 per the approved plan.

Checks:
- `docker-compose build app` — image rebuilt successfully (PHP 8.3.33, Debian trixie, Composer 2.10.3 present).
- `docker-compose up -d --force-recreate app` — container starts, entrypoint runs (`wait-for db:3306` → conditional composer/env bootstrap → `apache2-foreground`).
- `php artisan migrate --force` — ran against MySQL (`db` service) successfully.
- `php artisan migrate:status` — confirms `create_users_table`, `create_cache_table`, `create_jobs_table` all `Ran`.
- `curl https://localhost/` from the host → `200`, page title `Laravel`.
- `curl http://localhost/` from the host → `301` (redirect to HTTPS, per the existing vhost config) — expected.

Tests:
- `php artisan test` — 2/2 passed (`Tests\Unit\ExampleTest`, `Tests\Feature\ExampleTest`), default Laravel skeleton tests, run against the testing environment's SQLite config from `phpunit.xml` (unmodified).

Docker:
- Rebuilt `crm-app` image; `crm-app-1` container recreated and confirmed `Up`. `crm-db-1` (MySQL 8.0) and `crm-phpmyadmin-1` untouched and still running throughout.

Problems and resolution:
1. `entrypoint.sh` was never actually wired up as the container's entrypoint (base image's own `apache2-foreground` CMD ran directly) — fixed by adding an explicit `ENTRYPOINT` in the Dockerfile pointing at the bind-mounted `/docker/entrypoint.sh`.
2. Once wired up, `entrypoint.sh`/`wait-for.sh` failed with `$'\r': command not found` — both files had CRLF line endings from a Windows checkout (`core.autocrlf=true`, no `.gitattributes` forcing LF for shell scripts). Fixed by stripping CR characters and adding `crm/.gitattributes`.
3. Dockerfile `WORKDIR /var/public` did not match the actual bind-mounted path `/var/www` (`docker-compose.yml`) — fixed to `/var/www`.
4. `composer create-project laravel/laravel .` refused to run directly against `/var/www` because it already contained `docs/` and `public/` — scaffolded into a temp directory inside the container instead, then copied the result into `/var/www`, preserving the already-created `docs/`. During this step, an initial `rm -rf` accidentally targeted the *source* `public/` directory before copying; the standard Laravel `public/` files (`index.php`, `.htaccess`, `robots.txt`, `favicon.ico`) were recovered from Composer's own cached skeleton archive and copied into place.
5. The non-interactive installer defaulted to SQLite — `.env`/`.env.example` were pointed at the project's existing MySQL service instead.

Status:
DONE

Next step:
Awaiting explicit confirmation from the project owner before starting. Proposed: Step 2 — Authentication (thin `AuthController` + `AuthService` over Laravel's native `Auth`/`Password` facades: login, logout, session regeneration, `last_login_at`), per `PHASE-1-SPEC.md`.

---

## Step 2 — Authentication (login, logout)

Date: 2026-09-03

Goal:
Implement login/logout per `PHASE-1-SPEC.md`'s Authentication section: `/login` (email, password, remember_me), `/logout`, generic failure message, session regeneration on login, `last_login_at` recording, status-gated login, CSRF, rate limiting. No Users/Roles/Permissions/Password-Reset/Audit/full-Dashboard work.

Changed files:
- `app/Models/User.php` — added `last_login_at` cast (`datetime`) and an `isActive(): bool` helper.
- `config/auth.php` — added a `throttle.login` config block (`max_attempts`/`decay_minutes`, sourced from `AUTH_LOGIN_MAX_ATTEMPTS`/`AUTH_LOGIN_DECAY_MINUTES` env vars) so brute-force limits are configuration, not hardcoded.
- `app/Providers/AppServiceProvider.php` — registered the `login` named rate limiter (`RateLimiter::for('login', ...)`), keyed by `email|ip`, using the config values above.
- `routes/web.php` — added the `app/Modules/*/routes.php` auto-discovery loop (first real use of the module routing convention from `ARCHITECTURE.md`); added a minimal placeholder `GET /dashboard` (named `dashboard`, `auth`-protected) as the post-login landing page and as the route used to prove/test the `auth` middleware — explicitly not the real Dashboard module.
- `.env`, `.env.example` — added `SESSION_SECURE_COOKIE=true` (app is HTTPS-only per the existing vhost), `AUTH_LOGIN_MAX_ATTEMPTS=5`, `AUTH_LOGIN_DECAY_MINUTES=1`.

Created files:
- `database/migrations/2026_09_03_120000_add_status_and_last_login_at_to_users_table.php` — adds `status` (string, default `active`) and `last_login_at` (nullable timestamp) to `users`.
- `app/Modules/Auth/Requests/LoginRequest.php` — validates `email`, `password`, optional `remember_me`.
- `app/Modules/Auth/Services/AuthService.php` — `attempt()` (Auth::attempt + status check + session regenerate + `last_login_at` update, single generic `ValidationException` on any failure) and `logout()` (guard logout + session invalidate + token regenerate).
- `app/Modules/Auth/Controllers/AuthController.php` — thin: `create()` (show form), `store()` (validate via `LoginRequest`, delegate to `AuthService`, redirect), `destroy()` (delegate to `AuthService`, redirect).
- `app/Modules/Auth/routes.php` — `GET/POST /login` (behind `guest` middleware, `POST` additionally behind `throttle:login`), `POST /logout` (behind `auth`).
- `resources/views/auth/login.blade.php` — minimal unstyled login form (email/password/remember_me, validation errors, `@csrf`).
- `resources/views/dashboard.blade.php` — minimal placeholder page (welcome message, email, last login, logout button).
- `tests/Feature/Auth/LoginTest.php` — 6 tests: successful login, wrong password, non-existent email, identical error message for both failure cases, inactive user blocked, guest redirected from a protected route.
- `tests/Feature/Auth/LogoutTest.php` — 3 tests: authenticated logout, guest cannot reach `/logout`, dashboard unreachable after logout.

Deleted files:
None.

Dependencies:
None added — everything built on Laravel's native `Auth`, `Password`-adjacent session handling, `RateLimiter`, and validation, per the approved architecture (no Breeze/Fortify/etc.).

Checks:
- `php artisan migrate --force` — new migration applied to MySQL (`status`, `last_login_at` columns added).
- `php artisan route:list` — confirms `login` (GET/POST), `logout` (POST), `dashboard` (GET) all registered via the module auto-discovery loop.
- Live HTTP smoke test against the running Apache/MySQL stack (`curl` with a cookie jar, real CSRF token fetched from the form): wrong password → back to `/login` with an error; correct password → `302` to `/dashboard`; dashboard renders the welcome message; a fresh client with no cookies is redirected from `/dashboard` to `/login`; `POST /logout` → `302` to `/login`, dashboard unreachable afterward.
- Rate limiting: 6 rapid wrong-password attempts from the same client → attempts 1-5 return `302` (normal failed-login redirect), attempt 6 returns `429`, matching `AUTH_LOGIN_MAX_ATTEMPTS=5`.
- CSRF: `POST /login` without a token → `419`.

Tests:
- `php artisan test` — 11/11 passed (9 new Auth tests + the 2 pre-existing skeleton tests), run against the testing environment's SQLite config (unmodified `phpunit.xml`).

Docker:
No image/container changes this step; ran against the already-running `crm-app-1`/`crm-db-1` from Step 1.

Problems and resolution:
1. First draft of the "error message doesn't reveal which field was wrong" test read the session errors bag incorrectly (`Call to a member function get() on array`) — rewritten to use `assertSessionHasErrors(['email' => $message])` for both the wrong-password and non-existent-email cases instead of manually inspecting the session.

Status:
DONE

Next step:
Awaiting explicit confirmation from the project owner before starting. Proposed: Step 3 — Password reset (`/forgot-password`, `/reset-password`, thin `PasswordResetService` wrapping Laravel's native `Password` broker), per `PHASE-1-SPEC.md`.

---

## Session paused after Step 2

Date: 2026-09-03

Work paused here at the project owner's request (switching to other work). State to resume from:

- Steps 1 (Laravel skeleton + Docker) and 2 (Authentication: login/logout) are DONE, committed on `main` (commits `b8c7dce`, `6e322cd`), pushed to `https://github.com/gen2023/crm.git`.
- Stack is up and working: `docker-compose up -d` in `crm/`, app at `https://localhost`.
- One manually-created, **unseeded, uncommitted** dev user exists directly in the local MySQL `cmsdb` database for manual login testing: `admin@local.test` / `Password123!`. This is not a Seeder and will be superseded by the real `AdminUserSeeder` in the Seeders step — don't confuse it with seeded data when that step lands.
- Not yet implemented: Password reset, Users CRUD, Roles/Permissions (spatie/laravel-permission not installed yet), Audit Log, real Dashboard module, UI layout, Seeders.
- Next proposed step (unconfirmed): Step 3 — Password reset.
- Governance: continue one small confirmed step at a time; keep `docs/ARCHITECTURE.md`/`DECISIONS.md`/`IMPLEMENTATION-LOG.md` current; see those files plus `docs/PHASE-1-SPEC.md` for full approved scope and decisions before resuming.

---

## Step 3 — Password Reset

Date: 2026-09-03

Goal:
Implement the forgot-password / reset-password flow per `PHASE-1-SPEC.md`: `/forgot-password` (email), `/reset-password/{token}` (token, email, password, password_confirmation), built on Laravel's native `Password` broker (random, hashed, time-limited, single-use tokens — no custom token logic). No Users/Roles/Permissions/Audit/Dashboard work.

Changed files:
- `app/Modules/Auth/routes.php` — added `GET/POST /forgot-password` (`password.request`/`password.email`, behind `guest` + `throttle:password-reset` on POST) and `GET /reset-password/{token}` + `POST /reset-password` (`password.reset`/`password.update`, same middleware pattern). Route names follow Laravel's own convention exactly (`password.reset` is required as-is by the framework's default `ResetPassword` notification, which hardcodes a `route('password.reset', ...)` call).
- `config/auth.php` — added `throttle.password_reset` (`max_attempts`/`decay_minutes`, sourced from `AUTH_PASSWORD_RESET_MAX_ATTEMPTS`/`AUTH_PASSWORD_RESET_DECAY_MINUTES`), alongside the existing `throttle.login` block.
- `app/Providers/AppServiceProvider.php` — registered the `password-reset` named rate limiter (same `email|ip` keying pattern as `login`).
- `.env`, `.env.example` — added `AUTH_PASSWORD_RESET_MAX_ATTEMPTS=5`, `AUTH_PASSWORD_RESET_DECAY_MINUTES=1`.
- `resources/views/auth/login.blade.php` — added a "Забыли пароль?" link to `password.request`, and rendering for a `session('status')` flash message.

Created files:
- `app/Modules/Auth/Requests/ForgotPasswordRequest.php` — validates `email`.
- `app/Modules/Auth/Requests/ResetPasswordRequest.php` — validates `token`, `email`, `password` (`confirmed`, `Password::min(8)`).
- `app/Modules/Auth/Services/PasswordResetService.php` — `sendResetLink()` (thin wrapper over `Password::sendResetLink()`) and `reset()` (thin wrapper over `Password::reset()`, sets the new password via `forceFill` + the model's existing `hashed` cast, fires `PasswordReset` event, throws one generic `ValidationException` for any non-`PASSWORD_RESET` status).
- `app/Modules/Auth/Controllers/PasswordResetController.php` — thin: `create()`/`store()` for the forgot-password form, `edit()`/`update()` for the reset form.
- `resources/views/auth/forgot-password.blade.php`, `resources/views/auth/reset-password.blade.php` — minimal unstyled forms, consistent with `login.blade.php`.
- `tests/Feature/Auth/PasswordResetTest.php` — 5 tests: reset-link request notifies an existing user, reset-link request behaves identically (and sends nothing) for an unknown email, full reset with a valid token (including that the user can then log in with the new password and the old password is gone), reset fails with an expired token, a token cannot be reused.

Deleted files:
None.

Dependencies:
None added — built entirely on Laravel's native `Password` broker, `Notification::fake()`/`ResetPassword` notification, and validation, per the approved architecture (no custom `MailService`; transport is swapped later purely via `config/mail.php`/`.env`).

Design decisions made within this step's scope (not architecturally significant, no `DECISIONS.md` entry needed):
- The forgot-password response is **always** the same generic "if that email is registered, a link was sent" message, regardless of whether `Password::sendResetLink()` actually found a user — extends the same email-enumeration protection already applied to login in Step 2.
- A second, independent rate limiter (`password-reset`, IP+email keyed) was added on top of Laravel's own built-in per-email 60-second throttle inside the `Password` broker (`config/auth.php: passwords.users.throttle`, unchanged) — the broker's throttle alone doesn't protect `/reset-password` itself (token-guessing) or limit requests across many different email addresses from one IP.

Checks:
- `php artisan route:list` — confirms `password.request`, `password.email`, `password.reset`, `password.update` all registered via the existing module auto-discovery loop.
- Live HTTP smoke test against the running Apache/MySQL stack: requested a reset link for a temporary user, extracted the real reset URL from `storage/logs/laravel.log` (`MAIL_MAILER=log` in this environment), opened it (`200`), submitted a new password (`302` to `/login`), confirmed the old password no longer authenticates and the new one logs in successfully (`302` to `/dashboard`). Temporary user and log contents removed afterward.

Tests:
- `php artisan test` — 16/16 passed (5 new Password Reset tests + the 11 from Steps 1-2), run against the testing environment's SQLite config (unmodified `phpunit.xml`).

Docker:
No image/container changes this step. Containers had been stopped between sessions; brought back up via `docker-compose up -d` in `crm/`. Hit a host port conflict (80/443/8083/3306) with another unrelated local project (`wonder5`, same base Docker template) — resolved by stopping `wonder5`'s containers (project owner's explicit choice over remapping `crm`'s ports) rather than editing `docker-compose.yml`.

Problems and resolution:
None specific to the password-reset implementation itself; the only issue this session was the pre-existing-environment port conflict noted above.

Status:
DONE

Next step:
Roles/Permissions was confirmed as the next step (project owner's explicit choice, over Users) specifically to avoid ever shipping `/users` gated only by `auth` before real permissions exist. See Step 4 below.

---

## Step 4 — Roles & Permissions

Date: 2026-09-03

Goal:
Install `spatie/laravel-permission`, seed base roles/permissions, and build the Roles CRUD module (list/create/edit/view/delete + assign permissions to a role) per `PHASE-1-SPEC.md`. Explicitly deferred to the Users step (not in scope here): assigning roles *to a user*, and last-administrator protection — both are Users-module concerns per `PHASE-1-SPEC.md`'s own section split, and can't be built without a Users UI to build them into.

Changed files:
- `app/Models/User.php` — added Spatie's `HasRoles` trait, giving `$user->can('x.y')`/`assignRole()`/`getRoleNames()` etc.
- `database/seeders/DatabaseSeeder.php` — now calls `RolePermissionSeeder` instead of creating the skeleton's placeholder "Test User".
- `docs/IMPLEMENTATION-LOG.md` — this entry.

Created files:
- `config/permission.php`, `database/migrations/2026_09_03_170331_create_permission_tables.php` — published as-is from `spatie/laravel-permission` (`permissions`, `roles`, `model_has_permissions`, `model_has_roles`, `role_has_permissions`, all FKs `cascadeOnDelete()`).
- `database/migrations/2026_09_03_170500_add_description_to_permission_tables.php` — adds a nullable `description` column to `roles` and `permissions`, to match the "Описание" field from `PHASE-1-SPEC.md`'s Roles form. Spatie's own `name`/`guard_name` columns are left untouched; no separate `slug` column was added — `name` already serves as the unique, slug-shaped identifier used in every permission check, so a second slug field would just duplicate it.
- `database/seeders/RolePermissionSeeder.php` — seeds the 8 Phase-1 permissions (`users.*`, `roles.*`) and 3 roles (`admin`, `manager`, `user`); `admin` gets all 8 permissions, `manager`/`user` are created with none (assignable later via the Roles UI — not hardcoded). Idempotent (`firstOrCreate`), safe to re-run.
- `app/Modules/Roles/Requests/{StoreRoleRequest,UpdateRoleRequest}.php` — `name` (required, unique), `description` (nullable), `permissions.*` (must exist in the `permissions` table).
- `app/Modules/Roles/Services/RoleService.php` — `paginate()`, `allPermissions()`, `create()`, `update()`, `delete()`. Uses `syncPermissions()`; relies on the migration's DB-level cascade deletes for cleanup on role deletion (no manual detach logic needed).
- `app/Modules/Roles/Controllers/RoleController.php` — thin resource controller (index/create/store/show/edit/update/destroy), all delegating to `RoleService`.
- `app/Modules/Roles/routes.php` — `/roles` resource routes behind `auth` + one `can:roles.<action>` middleware per route.
- `resources/views/layouts/app.blade.php` — new minimal shared layout (nav with Dashboard/Roles links gated by `@can`, logout button, flash-status/error rendering). Introduced now because Roles adds 4 more authenticated pages that would otherwise duplicate the full HTML shell each `login.blade.php`/`dashboard.blade.php` had — this is plain Blade `@extends`/`@yield`, not the full Header+Sidebar+Content admin UI from the original spec, which remains its own future step.
- `resources/views/roles/{index,create,edit,show}.blade.php`, `resources/views/roles/partials/form.blade.php` — minimal, extend the new layout.
- `resources/views/dashboard.blade.php` — rewritten to extend `layouts.app`; now also shows the user's role names.
- `tests/Feature/Roles/RoleTest.php` — 8 tests: guest redirected to login, user without permission gets 403, admin can view the list, admin can create a role with permissions, role name uniqueness is enforced, admin can update a role's permissions, admin can delete a role, a user holding only `roles.view` can list but not create/gets 403.

Deleted files:
None (the skeleton's placeholder "Test User" seeding line was removed from `DatabaseSeeder`, not a file deletion).

Dependencies:
- `spatie/laravel-permission` (^8.3, installed 8.3.0) + its `spatie/laravel-package-tools` dependency. This was the one pre-approved, expected addition (`docs/DECISIONS.md` #4) — no other packages added.

Checks:
- `php artisan migrate --force` — both new migrations applied to MySQL.
- `php artisan db:seed --force` — roles/permissions seeded; `admin` role assigned to the existing manual dev user (`genodessa@gmail.com`) via `tinker` (not committed — same convention as the earlier temporary dev login).
- `php artisan route:list` — confirms all 7 `roles.*` routes registered via the existing module auto-discovery loop.
- Live HTTP smoke test against the running Apache/MySQL stack: logged in as `genodessa@gmail.com` (has `admin` role) → `/roles` returns `200` and lists all 3 seeded roles with correct permission counts (admin: 8); dashboard now shows "Роли: admin". A second temporary user with no role → `/roles` returns `403`. Temporary user removed afterward.

Tests:
- `php artisan test` — 24/24 passed (8 new Roles tests + the 16 from Steps 1-3), run against the testing environment's SQLite config (unmodified `phpunit.xml`); `RolePermissionSeeder` is seeded explicitly in `RoleTest::setUp()` via `$this->seed()`.

Docker:
No image/container changes this step; ran against the already-running stack from Step 3.

Problems and resolution:
None.

Status:
DONE

Next step:
Awaiting explicit confirmation. Proposed: Step 5 — Users (CRUD, status, role assignment via the now-existing Roles/Permissions, self-protection and last-administrator protection), per `PHASE-1-SPEC.md`.

---

## Step 5 — Users

Date: 2026-09-03

Goal:
Users CRUD (list/view/create/edit/deactivate/reactivate), role assignment, self-protection, and last-administrator protection, per `PHASE-1-SPEC.md`. This is the first module to use a `Policy` for object-level authorization, per `ARCHITECTURE.md`'s own worked example ("can this admin remove this specific role from this specific user without leaving zero admins").

Changed files:
- `app/Models/User.php` — added `'status'` to the `#[Fillable(...)]` attribute (see Problems below — this was a real, pre-existing bug this step exposed and fixed). `last_login_at` deliberately stays non-fillable; it's only ever set via `forceFill()` in `AuthService`.
- `app/Providers/AppServiceProvider.php` — registered `Gate::policy(User::class, UserPolicy::class)` (the policy lives in `app/Modules/Users/Policies`, outside Laravel's auto-discovery path, so it needs explicit registration — same reasoning as why Roles' Gate checks needed no such step: those are coarse permission checks, not policies).
- `resources/views/layouts/app.blade.php` — added a "Users" nav link, gated by `@can('users.view')`.
- `docs/IMPLEMENTATION-LOG.md` — this entry.

Created files:
- `app/Modules/Users/Requests/{StoreUserRequest,UpdateUserRequest}.php` — `name`, `email` (unique, ignoring self on update), `password` (required+confirmed on create, nullable+confirmed on update, `Password::min(8)`), `status` (`in:active,inactive`), `roles.*` (must exist in `roles`).
- `app/Modules/Users/Policies/UserPolicy.php` — `deactivate(actor, target)`: false if `actor->is(target)` (self-protection) or `target` is the last active user holding the `admin` role; `removeAdminRole(actor, target)`: false if `target` is the last active admin. Both funnel through one private `isLastActiveAdmin()` check.
- `app/Modules/Users/Services/UserService.php` — `paginate()`, `allRoles()`, `create()`, `update()` (calls `Gate::authorize('removeAdminRole', ...)` when the submitted roles would drop `admin` from a user who has it, and `Gate::authorize('deactivate', ...)` when the submitted status flips to `inactive`), `deactivate()` (dedicated action, same Gate check), `activate()` (no protection needed — reactivating is never destructive).
- `app/Modules/Users/Controllers/UserController.php` — thin resource controller (index/create/store/show/edit/update/destroy/activate), all delegating to `UserService`; `AuthorizationException` from the Policy checks is left to Laravel's default exception handling (renders as `403`, no custom try/catch needed).
- `app/Modules/Users/routes.php` — `/users` resource routes + `POST /users/{user}/activate`, behind `auth` + one `can:users.<action>` middleware per route (coarse permission gate; the Policy handles the object-level rules on top).
- `resources/views/users/{index,create,edit,show}.blade.php`, `resources/views/users/partials/form.blade.php` — extend `layouts.app`; the index hides the deactivate/activate action for the current user's own row (UX convenience only — the backend Policy is what actually enforces it).
- `tests/Feature/Users/UserTest.php` — 14 tests: guest redirected, 403 without permission, list, create-with-role-and-immediate-login, email uniqueness on create, edit, email uniqueness on edit, deactivate, reactivate, self-deactivation blocked, last-active-admin deactivation blocked, deactivation allowed when another active admin remains, admin-role removal blocked on the last active admin, admin-role removal allowed when another active admin remains.

Deleted files:
None.

Dependencies:
None added.

Checks:
- `php artisan route:list` — confirms all 8 `users.*` routes registered via the existing module auto-discovery loop.
- Live HTTP smoke test against the running Apache/MySQL stack, as `genodessa@gmail.com` (the sole seeded `admin`): `/users` → `200`; created a new user with the `manager` role through the real HTML form (`302`), confirmed via `tinker` the role was attached; deactivated that user through the real form (`302`, status flips to `inactive` in MySQL); attempted to deactivate **self** (the last active admin) through the real form → `403`, status unchanged. Temporary user removed afterward.

Tests:
- `php artisan test` — 38/38 passed (14 new Users tests + the 24 from Steps 1-4), run against the testing environment's SQLite config (unmodified `phpunit.xml`).

Docker:
No image/container changes this step; ran against the already-running stack.

Problems and resolution:
1. First test run: `admin can deactivate another user`, `admin can reactivate a user`, and `admin can be deactivated when another active admin remains` all failed — the target's `status` column silently stayed unchanged after `UserService::deactivate()`/`activate()`/`update()`. Root cause: `App\Models\User`'s `#[Fillable(...)]` attribute never included `status` (it was added to the `users` table in Step 2, but the model's mass-assignment allowlist was never updated), so `User::update(['status' => ...])` was silently dropping the field — Laravel's default mass-assignment protection fails closed rather than throwing. `create()` happened to look correct in testing only because the DB column's default (`active`) matched what was being requested; deliberately creating a user as `inactive` would have silently produced an `active` one. Fixed by adding `'status'` to `#[Fillable(...)]`. This is exactly the kind of correctness bug the "run tests after every step" rule (`ARCHITECTURE.md`/project rules) is meant to catch before it reaches real usage.
2. During the live HTTP check, an early `grep -o` for the CSRF token returned two identical matches on the page and, once, a corrupted concatenated value when piped through `sed` without `head -1`, producing a `419` on the first live create attempt — a test-tooling artifact, not an application bug; fixed by taking `head -1` of the match before extracting the value.

Status:
DONE

Next step:
Awaiting explicit confirmation. Remaining Phase 1 scope per `PHASE-1-SPEC.md`: Audit Log (wire the existing `AuditLogger`/`audit_logs` table into Users/Roles mutations), a real Dashboard (currently a placeholder from Step 2), custom error pages (403/404/422/500), and Seeders (a proper `AdminUserSeeder` to replace the manual `genodessa@gmail.com` dev account).

---

## Step 6 — Logging & Audit Log

Date: 2026-09-03

Goal:
Implement the two logging requirements left open from `PHASE-1-SPEC.md`: file-based Logging (failed logins, password-reset requests/failures, authorization errors — never passwords/tokens) and the DB-backed Audit Log (`audit_logs` + `AuditLogger`, wired into every Users/Roles mutation), per `ARCHITECTURE.md`. Dashboard, custom error pages, and Seeders explicitly deferred to later steps (agreed with the project owner: "visual/UI work later").

Changed files:
- `app/Modules/Auth/Services/AuthService.php` — logs `auth.login_failed` (`Log::warning`, email + IP only) whenever `Auth::attempt()` fails or the account is inactive; never logs the submitted password.
- `app/Modules/Auth/Services/PasswordResetService.php` — logs `auth.password_reset_requested` (info) on every `sendResetLink()` call regardless of outcome, `auth.password_reset_failed` (warning, email + Laravel's own status key) when `Password::reset()` doesn't return `PASSWORD_RESET`, and `auth.password_reset_completed` (info) on success. Never logs the token or the new password.
- `bootstrap/app.php` — registers two `report()` closures logging `auth.unauthenticated_access` (401) and `auth.access_denied` (403), each with `path`/`ip`(/`user_id` for 403). Both `AuthenticationException` and `AuthorizationException` are in Laravel's internal "don't report" list by default (they're "expected" responses) — `$exceptions->stopIgnoring([...])` was required before the custom reporters would actually run; each closure returns `false` afterward to suppress Laravel's own default trace-dump log line so only the one clean structured warning is written.
- `app/Modules/Users/Services/UserService.php` — injects `AuditLogger`; logs `user.created` (status, roles), `user.updated` (`getChanges()` with `password`/`updated_at` stripped, plus a `password_changed` boolean and a roles before/after diff — the changed password's hash is never included), `user.deactivated`, `user.activated`.
- `app/Modules/Roles/Services/RoleService.php` — injects `AuditLogger`; logs `role.created`, `role.updated` (permissions before/after), `role.deleted` (logged *before* the row is removed, since `subject_id` needs the still-live model).

Created files:
- `database/migrations/2026_09_03_180000_create_audit_logs_table.php` — `audit_logs`: `user_id` (nullable, `nullOnDelete`), `action`, `subject_type`, `subject_id`, `properties` (JSON, nullable), `created_at` only (no `updated_at` — audit rows are append-only).
- `app/Models/AuditLog.php` — `subject()` (`morphTo`), `actor()` (`belongsTo(User::class, 'user_id')`), `properties` cast to `array`, `const UPDATED_AT = null`.
- `app/Support/AuditLogger.php` — the one infrastructure-level service outside the module tree (per `ARCHITECTURE.md`): `log(string $action, Model $subject, array $properties = [])`, actor taken from `Auth::id()`.
- `tests/Feature/AuditLogTest.php` — 5 tests: user creation, user update (asserts the password is never present anywhere in the stored `properties`, including as a hash), user deactivation, role creation, and role deletion (asserts the audit row survives even though the role row is gone) all write the expected `audit_logs` entry.
- `tests/Feature/Auth/AuthorizationLoggingTest.php` — 2 tests: an unauthenticated request logs `auth.unauthenticated_access`; a forbidden request logs `auth.access_denied`.
- Extended existing files rather than new ones for the rest: `LoginTest::test_failed_login_is_logged_without_the_password`, `PasswordResetTest::test_reset_link_request_is_logged` and an assertion added to `test_reset_fails_with_an_expired_token` confirming `auth.password_reset_failed` is logged without the token.

Deleted files:
None.

Dependencies:
None added.

Checks:
- `php artisan migrate --force` — `audit_logs` table created on MySQL.
- Live HTTP smoke test against the running Apache/MySQL stack: a failed login produced `[...] local.WARNING: auth.login_failed {"email":"...","ip":"..."}` in `storage/logs/laravel.log` with no password field; creating a user through the real form produced a matching `audit_logs` row in MySQL (verified via `tinker`: `action=user.created`, correct `subject_id`, `user_id` = the acting admin, `properties` = status/roles); a plain user hitting `/roles` got `403` and produced `auth.access_denied {"user_id":...,"path":"roles","ip":"..."}` in the log. Log file and temporary users cleaned up afterward.

Tests:
- `php artisan test` — 47/47 passed (9 new: 5 Audit Log + 2 authorization-logging + 2 added to existing Auth test files), run against the testing environment's SQLite config (unmodified `phpunit.xml`); `Log::spy()`/`Log::shouldHaveReceived()` used to assert on log calls without touching the real log file during tests.

Docker:
No image/container changes this step; ran against the already-running stack.

Problems and resolution:
1. The first version of `AuthorizationLoggingTest` failed both cases (`InvalidCountException`, "called 0 times") — Laravel's exception handler silently drops `AuthenticationException`/`AuthorizationException` from reporting by default (they're in the framework's internal "don't report" list, since they're expected 401/403 outcomes), so the custom `report()` closures registered in `bootstrap/app.php` never ran at all. Fixed by calling `$exceptions->stopIgnoring([AuthenticationException::class, AuthorizationException::class])` before registering the closures, and returning `false` from each closure so Laravel's own default log line doesn't also fire alongside the custom one.

Status:
DONE

Next step:
Awaiting explicit confirmation. Remaining Phase 1 scope per `PHASE-1-SPEC.md`: a real Dashboard, custom error pages (403/404/422/500), and Seeders (a proper `AdminUserSeeder` to replace the manual `genodessa@gmail.com` dev account) — project owner has indicated visual/UI work (including these) will resume later.
