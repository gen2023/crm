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

---

## Step 7 — Custom error pages

Date: 2026-09-03

Goal:
Custom Blade views for 400/401/403/404/419/422/500 per `PHASE-1-SPEC.md`'s Error Handling section, so `APP_DEBUG=false` never exposes stack traces or internals — kept deliberately plain (project owner: visual/UI polish happens in a later, separate pass).

Changed files:
None — this step only adds views/tests.

Created files:
- `resources/views/errors/layout.blade.php` — shared minimal shell (`@yield('code')`, `@yield('message')`, a link home) so the 7 status pages don't each duplicate the full HTML document; matches the same `@extends`/`@yield` pattern already used for `layouts.app`.
- `resources/views/errors/{400,401,403,404,419,422,500}.blade.php` — one line of content each, extending the shared layout. Laravel automatically resolves `resources/views/errors/{status}.blade.php` for any HTTP exception with that status when `APP_DEBUG=false`; nothing else needed to be wired up.
- `tests/Feature/ErrorPagesTest.php` — a data-provider test rendering all 7 views directly (catches template/typo errors independent of triggering each real HTTP status), plus two real-request tests: an unknown route returns `404` with the custom page, and a permission-less user hitting `/roles` returns `403` with the custom page — both asserted with `config(['app.debug' => false])` so the assertions would fail if Laravel's debug page (Ignition) showed instead.

Deleted files:
None.

Dependencies:
None added.

Design notes (not architecturally significant):
- `401` and `422` are effectively unreachable through this app's current web flow and are included for spec completeness / future-proofing rather than because they fire today: unauthenticated access to a protected route redirects to `/login` (`302`) rather than rendering a 401 page (Laravel's `Authenticate` middleware only throws a renderable `AuthenticationException` for requests that expect JSON, which we have none of yet); validation failures on a normal web `POST` are caught by Laravel's default handling and redirected back with flashed errors (`302`) rather than rendering the 422 view. Both views are ready for when an API surface exists later.
- `419` (CSRF) and `403`/`404`/`400`/`500` are real, reachable outcomes today and were verified live.

Checks:
- `php artisan test` (see Tests below) plus a live check against the running Apache/MySQL stack with `APP_DEBUG` temporarily flipped to `false` (and back to `true` afterward — local dev keeps debug on): unknown route → `404`, custom page; `POST /login` with no CSRF token → `419`, custom page; a plain user hitting `/roles` → `403`, custom page. No Ignition/debug output in any of the three.

Tests:
- `php artisan test` — 56/56 passed (9 new: 7 view-render checks + 404 + 403 real-request checks), run against the testing environment's SQLite config (unmodified `phpunit.xml`).

Docker:
No image/container changes this step; ran against the already-running stack.

Problems and resolution:
1. The data-provider test initially used the legacy `@dataProvider` docblock annotation, which failed with `ArgumentCountError` (0 arguments passed) — this project's PHPUnit version (^12.5) requires the `#[PHPUnit\Framework\Attributes\DataProvider('method')]` attribute instead of the old annotation. Fixed by switching to the attribute form.

Status:
DONE

Next step:
Awaiting explicit confirmation. Remaining Phase 1 scope per `PHASE-1-SPEC.md`: a real Dashboard and Seeders (a proper `AdminUserSeeder` to replace the manual `genodessa@gmail.com` dev account) — both flagged by the project owner as visual/UI-adjacent work to resume later.

---

## Step 8 — Design tokens + Layout shell (sidebar, login)

Date: 2026-09-03

Goal:
Apply the project owner's visual design brief: color tokens, a collapsible 300px sidebar (icons-only when collapsed, state persisted client-side) replacing the old top nav bar, and a redesigned auth flow (login/forgot-password/reset-password). First of a 3-part visual pass (Steps 8/9/10); Users/Roles content and the real Dashboard are deliberately untouched here.

Changed files:
- `resources/views/layouts/app.blade.php` — replaced the top `<nav>` bar with a `.app-shell` = `.sidebar` (300px, `.collapsed` state → 76px, icon-only) + `.content`. Sidebar: logo + collapse toggle at top, nav links (Dashboard/Users/Roles, still `@can`-gated, now with an `active` state via `request()->routeIs()`) with icons, logout as a sidebar item at the bottom. Introduced CSS custom properties (`--bg-page: #F7F0E3`, `--bg-input: #F4F6FA`, `--color-brand: #00a1df`, `--color-accent: #f7a001`) and a `.card` class for Step 9/10 to build on. All pre-existing selectors Users/Roles pages depend on (`.btn`, `.btn-danger`, `table/th/td`, `.status`, `.errors`, `.permissions`, form inputs) were kept as-is so those pages keep rendering correctly until Step 9 restyles them. A small inline `<script>` toggles `.collapsed` and persists the choice to `localStorage` (wrapped in `try/catch` — private-browsing/storage-blocked contexts degrade to "always expanded" rather than throwing).
- `resources/views/auth/{login,forgot-password,reset-password}.blade.php` — rewritten to `@extends('auth.layout')`, using the new shared shell instead of each duplicating its own `<html>`/`<style>`.

Created files:
- `resources/views/components/icon.blade.php` — a single anonymous Blade component (`<x-icon name="..."/>`) with a small hand-written inline-SVG lookup table (`dashboard`, `users`, `roles`, `chevron-left`, `chevron-right`, `logout`, `eye`, `pencil`, `trash`, `plus`). No icon library dependency; `eye`/`pencil`/`trash`/`plus`/`chevron-right` are defined now but only get used starting Step 9 — defining the whole small vocabulary once avoids touching this file again for a few icons.
- `resources/views/auth/layout.blade.php` — shared shell for the three unauthenticated pages: top-left "GenCrm" logo (`--color-brand`), centered white rounded card (`border-radius: 12px`, bordered) on the `--bg-page` background, inputs styled per the brief (rounded, `--bg-input`, no border), `.btn-primary` (`--color-accent` background, black text).

Deleted files:
None.

Dependencies:
None added — no icon library, no CSS framework, no build step; plain CSS custom properties and inline SVG, consistent with the earlier decision to skip Vite/Node for this admin UI.

Checks:
- `php artisan view:clear` + live check against the running stack: `/login` HTML contains the `F7F0E3`/`F4F6FA`/`f7a001` color values and the "GenCrm" logo text; after logging in, `/dashboard` HTML contains the sidebar markup and all three nav labels (Dashboard/Users/Roles).

Tests:
- `php artisan test` — 56/56 still passing (no test asserted on the old nav markup, so no test changes were needed; this step was purely visual/structural).

Docker:
No image/container changes.

Problems and resolution:
None.

Status:
DONE

Next step:
Step 9 — apply the same design system to Users/Roles (icon actions instead of text links, white bordered cards for create/edit/view), per the agreed 8/9/10 sequence.

---

## Step 9 — Users/Roles on the design system

Date: 2026-09-03

Goal:
Apply Step 8's tokens/components to Users and Roles: row actions become icon buttons (view/edit/deactivate-activate/delete) instead of text links, and every page (list included, for consistent padding/border per the brief) sits inside a `.card`.

Changed files:
- `resources/views/layouts/app.blade.php` — added `.row-actions`/`.icon-btn` (30px square, transparent, `.danger`/`.success` hover-color variants) for row-level icon actions; `.btn` became a flex container so an icon can sit next to its label; extended the shared input styling to `select` and `input[type=password]` (previously only `text`/`email`); table no longer sets its own white background (it now always sits inside a `.card`, which already provides it) and its row divider color was softened to match the new palette.
- `resources/views/components/icon.blade.php` — added a `check` icon (used for the "reactivate" action).
- `resources/views/users/{index,create,edit,show}.blade.php` — index: create button gets a `plus` icon; each row's actions are now `eye`/`pencil`/`trash`/`check` icon buttons (`title` attributes for hover text — screen-reader/accessibility label), same permission gating and same-user self-action hiding as before; table wrapped in `.card`. create/edit/show: form and detail content wrapped in `.card`.
- `resources/views/roles/{index,create,edit,show}.blade.php` — identical treatment (`eye`/`pencil`/`trash`, `.card` wrapping).

Created files:
None.

Deleted files:
None.

Dependencies:
None added.

Checks:
- `php artisan view:clear` + live check against the running stack, logged in as `genodessa@gmail.com`: `/users` and `/roles` both return the table inside `class="card"` with `.icon-btn`/`.row-actions` markup present; `/users/create` form is inside `class="card"`.

Tests:
- `php artisan test` — 56/56 still passing; no test asserted on the old text-link markup ("Просмотр"/"Редактировать" etc. were never asserted by name in `UserTest`/`RoleTest`, only status codes and data), so no test changes were needed.

Docker:
No image/container changes.

Problems and resolution:
None.

Status:
DONE

Next step:
Step 10 — real Dashboard module (recent-logins card sourced from `audit_logs`, replacing the Step 2 placeholder route/view) and `AdminUserSeeder`, per the agreed 8/9/10 sequence and the detailed spec already shared with the project owner.

---

## Step 10 — Dashboard module + AdminUserSeeder

Date: 2026-09-03

Goal:
Replace the Step 2 placeholder `/dashboard` closure with a real `app/Modules/Dashboard` module (welcome card + a "recent logins" card, up to 5, who+date, per the design brief) and add `AdminUserSeeder` so the working admin account is reproducible via `db:seed` instead of only existing as an ad-hoc `tinker`-created row. Last two items of Phase 1's remaining scope, per the plan shared with the project owner.

Changed files:
- `app/Modules/Auth/Services/AuthService.php` — on successful login, now also calls `AuditLogger::log('auth.login', $user)` (actor and subject are the same user). This is the data source for the dashboard's login-history card — reuses the existing `audit_logs` table/architecture rather than adding a dedicated login-history table.
- `routes/web.php` — removed the placeholder `GET /dashboard` closure (moved into the new module's `routes.php`, picked up by the same auto-discovery loop as every other module).
- `database/seeders/DatabaseSeeder.php` — now also calls `AdminUserSeeder` (after `RolePermissionSeeder`, so the `admin` role exists to assign).
- `.env`, `.env.example` — added `ADMIN_NAME`/`ADMIN_EMAIL`/`ADMIN_PASSWORD`. Local `.env` (not committed) carries the real values already in use (`genodessa@gmail.com` / the existing password), so seeding is a no-op reconciliation of the account that already existed from earlier manual `tinker` steps. `.env.example` carries generic placeholders with a comment not to use them as-is.

Created files:
- `app/Modules/Dashboard/Services/DashboardService.php` — `recentLogins(int $limit = 5)`: `AuditLog::where('action', 'auth.login')->with('actor')->latest('id')->limit($limit)->get()`. Ordered by `id` rather than `created_at` deliberately — an append-only table's row id is already a strict, collision-free recency order, whereas same-second timestamps (easily hit in fast test runs, and possible in real traffic bursts) would make `created_at` ordering ambiguous.
- `app/Modules/Dashboard/Controllers/DashboardController.php` — thin: `index()` hands the current user and `DashboardService::recentLogins()` to the view.
- `app/Modules/Dashboard/routes.php` — `GET /dashboard` (`auth` middleware, named `dashboard`, unchanged from before).
- `resources/views/dashboard/index.blade.php` — two cards per the design brief: "История заходов" (table, who + date, up to 5) listed first, then a "Профиль" card (name/email/roles/last login — the original Phase 1 spec's welcome-message content). Replaces `resources/views/dashboard.blade.php` (deleted).
- `database/seeders/AdminUserSeeder.php` — `User::updateOrCreate(['email' => env('ADMIN_EMAIL', ...)], ['name' => ..., 'password' => ..., 'status' => 'active'])` + `assignRole('admin')`. Guarded by `app()->environment('local')` — skips (with a console warning) everywhere else, so a routine `db:seed` in a shared/production environment can never silently produce an admin account with a default/guessable password; provisioning a production admin stays a deliberate, separate action.
- `tests/Feature/DashboardTest.php` — 3 tests: guest redirected, an authenticated user sees their own name/email, and the recent-logins card shows only the 5 most recent of 6 real logins performed through the actual `/login` endpoint (the oldest is confirmed absent).
- `tests/Feature/AdminUserSeederTest.php` — 2 tests: with the environment forced to `local` (`$this->app->detectEnvironment(fn () => 'local')`) and `ADMIN_*` env vars overridden, the seeder creates the expected user with the `admin` role; with the default `testing` environment, the seeder is a no-op (`assertDatabaseCount('users', 0)`).
- `tests/Feature/Auth/LoginTest.php` — added an `assertDatabaseHas('audit_logs', ['action' => 'auth.login', ...])` assertion to the existing successful-login test, covering the new `AuthService` behavior.

Deleted files:
- `resources/views/dashboard.blade.php` — superseded by `resources/views/dashboard/index.blade.php`.

Dependencies:
None added.

Checks:
- `php artisan route:list` — `dashboard` now resolves to `App\Modules\Dashboard\Controllers\DashboardController@index` instead of a closure.
- `php artisan db:seed --force` against MySQL — `AdminUserSeeder` ran (APP_ENV=local) and left `genodessa@gmail.com` unchanged (`status=active`, role `admin`) — confirmed idempotent via `tinker`.
- Live HTTP check against the running stack: logged in as `genodessa@gmail.com`, `/dashboard` (`200`) contains "История заходов" and the user's own name/email.

Tests:
- `php artisan test` — 61/61 passed (5 new: 3 Dashboard + 2 AdminUserSeeder, plus 1 assertion added to an existing Login test), run against the testing environment's SQLite config (unmodified `phpunit.xml`).

Docker:
No image/container changes.

Problems and resolution:
1. `AdminUserSeederTest`'s "creates an admin" case initially failed (`assertNotNull` on a `null` user) even after correctly forcing the environment to `local` via `detectEnvironment()`. Root cause: Laravel's `env()` helper does not re-read `putenv()` changes made after the application has already booted — it was still returning the real `.env` file's `ADMIN_EMAIL` value rather than the test's override. A raw debug script confirmed `getenv()` reflected the override immediately while `env()` did not, and that additionally setting `$_ENV`/`$_SERVER` alongside `putenv()` made `env()` pick it up too. Fixed by setting all three for each overridden variable (and unsetting all three in a `finally` block).

Status:
DONE

Next step:
Phase 1 scope per `PHASE-1-SPEC.md` is now functionally complete (Foundation, Docker, Authentication, Password Reset, Users, Roles, Permissions, User Status, Audit Log, Dashboard, Validation, Error Handling, base UI, Security, Testing, Seeders all implemented and tested). Remaining before formally closing Phase 1: a pass against the full Definition of Done checklist in `PHASE-1-SPEC.md`, and a decision from the project owner on whether any further UI polish is wanted before moving to Phase 2 scope (Customers/Products/Orders/...).

---

## Step 11 — UI fixes (dark sidebar, button/link colors, icon-size bug) + Dashboard user-menu + Profile page

Date: 2026-09-03

Goal:
Project owner feedback on Steps 8-10's visual pass: button text should be white (not black), the sidebar should be a dark `#242424` background with white link text (hover `#f8b02c`), the sidebar collapse button was invisible (though clickable), colors should be centralized as CSS variables, the Dashboard's "Профиль" card should be removed in favor of a top-right name + popup ("Просмотр" / "Выход"), and the flow should move on to Customers next. Also captures a new `docs/BACKLOG.md` note about a future API module (Products/Orders/Users CRUD) mentioned in passing.

Changed files:
- `resources/views/layouts/app.blade.php` — full token rewrite: `:root` now defines `--color-bg-page`, `--color-bg-input`, `--color-primary`, `--color-accent`, `--color-accent-hover`, `--color-text-on-accent`, `--color-danger`, `--color-success`, `--color-sidebar-bg`, `--color-sidebar-text`, `--color-card-border`, plus the existing sidebar-width tokens. Sidebar background → `var(--color-sidebar-bg)` (`#242424`); link/toggle text → `var(--color-sidebar-text)` (white); hover/active state → `var(--color-accent-hover)` (`#f8b02c`), applied as a text-color change (no background swap, matching "текст ссылок... ховер ссылок" literally). `.btn`/`.btn-danger` text → `var(--color-text-on-accent)` (white). Added a base `svg.icon { width: 20px; height: 20px; }` rule (root cause fix for the invisible-but-clickable collapse button — see Problems). Removed the sidebar's bottom logout block entirely; added a `.topbar` at the top of every page's content area with a `.user-menu` (current user's name + a chevron, `hidden`-attribute dropdown with "Просмотр" → `/profile` and a "Выход" form) — implemented in the shared layout (not only on the Dashboard template) so logout stays reachable from every authenticated page now that it's no longer in the sidebar; toggled via a second small vanilla-JS block (click to open, click-outside or Escape to close).
- `resources/views/auth/layout.blade.php` — same token renaming for consistency (`--color-primary`, `--color-accent`, `--color-text-on-accent`, etc.) and `.btn-primary` text → white, matching the app shell.
- `resources/views/components/icon.blade.php` — added `chevron-down` (user-menu trigger).
- `resources/views/dashboard/index.blade.php` — removed the "Профиль" card; only "История заходов" remains.
- `app/Modules/Dashboard/Controllers/DashboardController.php` — no longer passes `user` to the view (nothing in the template used it once the profile card was removed).
- `tests/Feature/DashboardTest.php` — the "user sees their own info" test no longer asserts the email is visible on `/dashboard` (that content moved to `/profile`); still asserts the name is visible, now via the topbar.

Created files:
- `app/Modules/Profile/Controllers/ProfileController.php` — `show()`, no `Service` (nothing beyond handing the already-authenticated user to the view — no business logic to justify one, per the architecture's "no abstractions we don't need" rule).
- `app/Modules/Profile/routes.php` — `GET /profile` (`auth` middleware only, named `profile.show`) — deliberately **not** gated by `users.view`: viewing your own data must not depend on holding the `users.*` permission, otherwise a plain `user`/`manager`-role account with no such permission could never open "Просмотр" at all.
- `resources/views/profile/show.blade.php` — the same fields the removed Dashboard card had (name, email, roles, last login), in a `.card`.
- `tests/Feature/ProfileTest.php` — 2 tests: guest redirected to login; any authenticated user (regardless of permissions) can view their own profile.
- `docs/BACKLOG.md` — new file for not-yet-scoped, forward-looking notes (distinct from `DECISIONS.md`/`PHASE-1-SPEC.md`). First entry: a future API module for Products/Orders/Users CRUD, with pointers to the relevant `ARCHITECTURE.md`/`DECISIONS.md` constraints for whoever scopes it later.

Deleted files:
None.

Dependencies:
None added.

Checks:
- Live HTTP check against the running stack, logged in as `genodessa@gmail.com`: `/dashboard` HTML contains the `#242424`/`#f8b02c` token values, the `user-menu-dropdown` markup, and "История заходов" (no "Профиль"); the dropdown's "Просмотр" link resolves to `/profile`; `/profile` (`200`) shows the name and email.

Tests:
- `php artisan test` — 63/63 passed (2 new `ProfileTest` cases; `DashboardTest` adjusted, not net-new).

Docker:
No image/container changes.

Problems and resolution:
1. The project owner reported the sidebar collapse button was invisible but still clickable. Root cause: `.sidebar-link .icon { width: 20px; height: 20px; }` was scoped only to nav links — the toggle button (`.sidebar-toggle`) had no matching selector, so its `<svg>` rendered at the browser's default replaced-element size instead of a sensible icon size. Fixed with a global `svg.icon` base rule so every icon gets a sane default regardless of its container, with more specific selectors (`.btn .icon`, `.icon-btn .icon`) only overriding size where they actually need to differ.

Status:
DONE

Next step:
Per the project owner: move on to the Customers module (Phase 2 scope). Not yet spec'd — awaiting requirements (fields, permissions, relation to Orders) before planning a step, per the usual workflow.

---

## Step 12 — Customers (first Phase 2 module)

Date: 2026-09-03

Goal:
First Phase 2 module: Customers CRUD (list/create/edit/view; no delete — see Design notes), gathered from the project owner over a short Q&A rather than a written spec, since Phase 2 doesn't have a `PHASE-2-SPEC.md` yet.

Fields, as clarified over the discussion:
- Manually entered: `name` (ФИО), `phone` (unique), `email` (nullable, not unique).
- Captured, not manually typed (nullable, shown read-only): `ip`, `utm` (single JSON column — the owner explicitly wants one field holding `utm_source=...` etc. as JSON, not separate `utm_source`/`utm_medium`/`utm_campaign` columns).
- Order aggregates (owner: "да, поля в БД" — real columns now, defaulted, to be kept current by the future Orders module): `total_orders_amount`, `last_order_at`, `orders_count`, `completed_orders_count`, `cancelled_orders_count`.
- `reliability()` — **not stored**; a Model accessor computed as `completed / (completed + cancelled) * 100`, rounded to 1 decimal, `null` when both counts are 0 (order statuses per the owner: новый/доставляется/завершён/отменён — only завершён/отменён count toward reliability).
- "Источник" (source) was in the original field list but the owner clarified mid-discussion it belongs to **Orders**, not Customers — see the new `docs/BACKLOG.md` entry.

Changed files:
- `database/seeders/RolePermissionSeeder.php` — added `customers.view`/`customers.create`/`customers.edit` to `PERMISSIONS` (no `customers.delete` — no delete action exists to gate). Also fixed a real bug found while re-seeding live (see Problems): now calls `PermissionRegistrar::forgetCachedPermissions()` before creating new permissions and again before `syncPermissions()`.
- `resources/views/layouts/app.blade.php` — added a "Customers" sidebar link (`@can('customers.view')`, new `customers` icon), positioned above Users.
- `resources/views/components/icon.blade.php` — added a `customers` icon (ID-card style, distinct from the existing multi-person `users` icon).
- `docs/BACKLOG.md` — new entry: the future "Sources/Integrations" module (marketplace order-ingestion: Prom, Rozetka, Maudau, OLX, Epicentr, Kasta, Umall — API keys + pulling orders in), which is where "source" actually belongs.

Created files:
- `database/migrations/2026_09_03_190000_create_customers_table.php`.
- `app/Models/Customer.php` — `casts()`: `utm` → `array`, `total_orders_amount` → `decimal:2`, `last_order_at` → `datetime`; `reliability(): ?float` accessor described above.
- `database/factories/CustomerFactory.php`.
- `app/Modules/Customers/Requests/{StoreCustomerRequest,UpdateCustomerRequest}.php` — `name` required, `phone` required + unique (ignoring self on update), `email` nullable/email. Order-aggregate fields and `ip`/`utm` are deliberately **not** in the validated rule set, so nothing submitted under those keys can ever reach `CustomerService`.
- `app/Modules/Customers/Services/CustomerService.php` — `paginate()`, `create()`, `update()`; both mutation methods only ever write `name`/`phone`/`email` (the aggregate/tracking fields are never touched here — they wait for the Orders module), and both call `AuditLogger` (`customer.created`/`customer.updated`), matching the Users/Roles convention.
- `app/Modules/Customers/Controllers/CustomerController.php` — thin: index/create/store/show/edit/update (no `destroy`).
- `app/Modules/Customers/routes.php` — `/customers` resource routes (no `DELETE`), behind `auth` + one `can:customers.<action>` middleware per route.
- `resources/views/customers/{index,create,edit,show}.blade.php`, `resources/views/customers/partials/form.blade.php` — same card/icon-button pattern as Users/Roles; create/edit forms only expose `name`/`phone`/`email`; `show` additionally displays `ip`, raw `utm` JSON, and the order-aggregate block (count/sum/last-order-date/reliability) as read-only.
- `tests/Feature/Customers/CustomerTest.php` — 9 tests: guest redirected, 403 without permission, list, create, phone uniqueness on create, **aggregate fields cannot be set through the create form** (posts `orders_count`/`total_orders_amount`, asserts they stayed at 0), edit, phone uniqueness on edit (ignoring self), and `reliability()` correctness (no orders → `null`, 3 completed/1 cancelled → `75.0`, 0 completed/2 cancelled → `0.0`).

Deleted files:
None.

Dependencies:
None added.

Design notes (not architecturally significant):
- No delete/deactivate action for Customers in this step — unlike Users, nothing was specified about a customer lifecycle, and deleting a customer with linked order history would be a much riskier default than deleting a user; left out rather than guessed at. Easy to add once the owner decides what it should mean (hard delete vs. some status).
- `ip`/`utm` are excluded from the create/edit forms on purpose: they're metadata a lead-capture integration would populate, not something a staff member operating this admin UI would type in by hand.

Checks:
- `php artisan migrate --force` — `customers` table created on MySQL.
- `php artisan route:list` — confirms all 6 `customers.*` routes.
- Live HTTP check against the running stack, logged in as `genodessa@gmail.com`: created a customer with a Cyrillic name through the real form, confirmed it in MySQL via `tinker` and in the `/customers` list page. Temporary customer and log contents removed afterward.

Tests:
- `php artisan test` — 72/72 passed (9 new Customers tests), run against the testing environment's SQLite config (unmodified `phpunit.xml`).

Docker:
No image/container changes.

Problems and resolution:
1. First live create attempt returned `403` — the newly added `customers.*` permission slugs existed in code (`RolePermissionSeeder`) but the live MySQL database hadn't been re-seeded yet. Expected, not a bug; fixed by running `db:seed --force`.
2. Re-seeding then failed with `Spatie\Permission\Exceptions\PermissionDoesNotExist: There is no permission named 'customers.view'` — thrown from `syncPermissions()` immediately after the same seeder had just created that exact permission a few lines earlier. Root cause: Spatie caches the full permission list in the app's default cache store (`database` here), which persists across process runs; a prior seeding of the original 8 permissions had already populated that cache, so this run's `syncPermissions()` call read the stale cached list and didn't see the 3 new slugs, even though they were already committed to the `permissions` table. Fixed by calling `app(PermissionRegistrar::class)->forgetCachedPermissions()` before creating permissions and again right before `syncPermissions()` — makes the seeder safe to re-run after `PERMISSIONS` gains new entries, not just on a first run against an empty database.
3. Second live create attempt returned `500`: `SQLSTATE[HY000]: 1366 Incorrect string value ... for column 'name'`. The byte sequence in the error was not valid UTF-8 (invalid continuation bytes) — traced to the Cyrillic text being typed directly into a `curl --data-urlencode` argument in this session's Bash tool, which mangled the encoding somewhere in the Windows/Git-Bash shell layer before curl ever saw it. Confirmed harmless (not an app or schema bug — `config('database.connections.mysql.charset')` is `utf8mb4`) by resending the same request with the Cyrillic value pre-encoded as literal `%D0%A1...` UTF-8 percent-escapes in a payload file (bypassing shell string interpretation entirely) — that request succeeded and stored "Смоук Тест" correctly. Testing-tooling artifact only; no code change. Worth remembering for any future live check involving non-ASCII input from this shell.

Status:
DONE

Next step:
Awaiting the project owner's next direction. Candidates: continue Phase 2 with Products or Orders (Orders would let the Customer aggregate fields/reliability actually start populating), or the Sources/Integrations module now noted in `docs/BACKLOG.md`.

---

## Session paused after Step 12

Date: 2026-09-03

Work paused here for the night at the project owner's request. State to resume from:

- Steps 1-12 all DONE, committed on `main` up to `4c4fab9`, pushed to `https://github.com/gen2023/crm.git`.
- Phase 1 (Foundation/Auth/Users/Roles/Permissions/Audit Log/Dashboard/Error Pages/Seeders) is functionally complete. Phase 2 has one module so far: Customers (Step 12).
- Stack: `docker-compose up -d` in `crm/`, app at `https://localhost`. Login: `genodessa@gmail.com` / `Genodessa2026!` (now reproducible via `AdminUserSeeder`, no longer only a manual `tinker` row).
- Not yet started: Products, Orders, Sources/Integrations (see `docs/BACKLOG.md` for the Sources module and the future API-module note).
- No open questions blocking the next step — see "Next step" just above for the menu of what could come next; nothing has been decided yet.

---

## Step 13 — Products

Date: 2026-09-04

Goal:
Products CRUD (list/create/edit/view/delete), gathered via a short Q&A: name, SKU (unique), price, stock, plus description/category/photo (project owner chose the wider field set over the bare minimum). Built first because Orders' line items need Products to reference.

Fields:
- `name`, `sku` (unique), `price` (decimal 12,2), `stock` (unsigned int), `description` (nullable text), `category` (nullable string, free text — not a managed lookup table, same reasoning as Customers' fields), `photo_path` (nullable, an uploaded image stored on the `public` disk).

Changed files:
- `database/seeders/RolePermissionSeeder.php` — added `products.view`/`create`/`edit`/`delete`.
- `resources/views/layouts/app.blade.php` — added a "Products" sidebar link (`@can('products.view')`).
- `resources/views/components/icon.blade.php` — added `products` and `orders` icons (the latter unused until Step 14, added at the same time to avoid touching this file twice).

Created files:
- `database/migrations/2026_09_04_090000_create_products_table.php`.
- `app/Models/Product.php` — `orderItems(): HasMany` (to `OrderItem`, added in Step 14), `photoUrl(): ?string` via `Storage::disk('public')`.
- `database/factories/ProductFactory.php`.
- `app/Modules/Products/Requests/{StoreProductRequest,UpdateProductRequest}.php`, `app/Modules/Products/Services/ProductService.php` (`create`/`update` handle the optional uploaded photo — stored via `UploadedFile::store('products', 'public')`, old file deleted on replace; `delete()` throws a `ValidationException` if the product has any `order_items`, so a product actually used in order history can't be removed), `app/Modules/Products/Controllers/ProductController.php`, `app/Modules/Products/routes.php` (full resource incl. `DELETE`, behind `can:products.<action>`).
- `resources/views/products/{index,create,edit,show}.blade.php`, `resources/views/products/partials/form.blade.php` — card/icon pattern; create/edit forms use `enctype="multipart/form-data"` for the photo field.
- `tests/Feature/Products/ProductTest.php` — 8 tests: guest redirected, 403, list, create, SKU uniqueness, edit, delete (no orders), delete blocked when an `OrderItem` references the product.

Deleted files:
None.

Dependencies:
None added — file uploads use Laravel's built-in `Storage`/`UploadedFile`, no package needed. Ran `php artisan storage:link` once (creates `public/storage` → `storage/app/public`) so uploaded photos are actually web-reachable.

Design notes:
- `category` is free text, not a managed reference table — consistent with how "source" was left as free text; no CRUD-worthy volume of categories was described.
- Delete is allowed (unlike Customers/Users' "no hard delete" pattern) but guarded by the order-history check above — a product with no order history is safe to remove outright.

---

## Step 14 — Orders (+ order items, Customer aggregate sync)

Date: 2026-09-04

Goal:
Orders CRUD (list/create/edit/view; no delete — real transactions, same reasoning as Customers) with multiple line items per order (confirmed explicitly over the alternative of one product per order), and wiring order mutations into `Customer::recalculateOrderStats()` so the aggregate fields laid down in Step 12 (orders_count, total_orders_amount, last_order_at, completed/cancelled counts, reliability) finally get populated.

Fields, as clarified over the discussion:
- `customer_id` (required), `status` (`new`/`shipping`/`completed`/`cancelled`, Russian labels only at the UI layer), `source` (marketplace, free text — "belongs to Orders, not Customers" per the owner's Step 12 clarification), `delivery_address`, `payment_method`, `comment`, `marketplace_order_id`, `marketplace_order_name` (the marketplace's own order reference — useful once the future Sources/Integrations module pulls orders in), `total_amount` (computed, not user-entered — see below).
- `order_items`: `product_id`, `quantity`, `price` — **price is always snapshotted from the product's current price at save time**, never taken from client input, so a later product price change doesn't retroactively rewrite historical order totals.

Changed files:
- `app/Models/Customer.php` — added `orders(): HasMany` and `recalculateOrderStats(): void`. The latter **recomputes from the `orders` table on every call** (four aggregate queries + `save()`) rather than incrementally patching counters — simpler to reason about, immune to drift bugs, acceptable cost at this scale. Called by `OrderService` after every create/update.
- `database/seeders/RolePermissionSeeder.php` — added `orders.view`/`create`/`edit` (no `orders.delete` — no delete route to gate).
- `resources/views/layouts/app.blade.php` — added an "Orders" sidebar link.

Created files:
- `database/migrations/2026_09_04_091000_create_orders_table.php` — creates both `orders` and `order_items` (`order_id` FK `cascadeOnDelete()`; `product_id` FK left at the DB default RESTRICT-like behavior, matching `ProductService::delete()`'s application-level check).
- `app/Models/Order.php` — `STATUS_*` constants + `STATUS_LABELS` map (Russian labels, English-keyed storage — avoids Cyrillic values in `WHERE status = ...` queries), `customer()`, `items()`, `statusLabel()`.
- `app/Models/OrderItem.php` — `order()`, `product()`, `lineTotal()`.
- `database/factories/{OrderFactory,OrderItemFactory}.php`.
- `app/Modules/Orders/Requests/{StoreOrderRequest,UpdateOrderRequest}.php` — `items` required array, `min:1`, each item's `product_id`/`quantity` validated.
- `app/Modules/Orders/Services/OrderService.php` — `create()`/`update()` wrap the order + `syncItems()` (delete-and-recreate all line items, snapshot price, recompute `total_amount`) + `$order->customer->recalculateOrderStats()` in one `DB::transaction()`. `update()` additionally recalculates the *previous* customer's stats too when an order is reassigned to a different customer, so neither customer is left with stale aggregates.
- `app/Modules/Orders/Controllers/OrderController.php` — thin; `create()`/`edit()` hand the full customer/product lists and `Order::STATUS_LABELS` to the view for the form's selects.
- `app/Modules/Orders/routes.php` — no `DELETE` route.
- `resources/views/orders/{index,create,edit,show}.blade.php`, `resources/views/orders/partials/form.blade.php` — the form includes a small vanilla-JS repeatable line-item row group (add/remove product+quantity pairs via a `<template>`, at least one row always required client-side), consistent with the sidebar-toggle/user-menu scripting already in `layouts.app`.
- `tests/Feature/Orders/OrderTest.php` — 7 tests: guest redirected, 403, create-with-items computes `total_amount` correctly, at least one item required, creating an order updates customer aggregates, marking an order `completed` yields `reliability() === 100.0`, a `cancelled` order alongside a `completed` one yields `reliability() === 50.0`, reassigning an order's customer updates both customers' aggregates correctly.

Deleted files:
None.

Dependencies:
None added.

Design notes (not architecturally significant):
- No delete action for Orders, matching the Customers precedent — a real transaction record shouldn't be hard-deletable; correcting a mistake means editing status/items instead.
- Stock is **not** automatically decremented/restored by order creation or status changes in this step — that's real additional scope (partial fulfillment, restock-on-cancel edge cases) that wasn't part of what was discussed; `stock` stays a manually-managed Product field for now. Flagged here so it isn't mistaken for an oversight later.
- `total_orders_amount` sums **all** of a customer's orders regardless of status (including cancelled) — the owner described it as a plain aggregate without specifying an exclusion; easy to change to "non-cancelled only" later if that's not what's wanted.

Checks:
- `php artisan migrate --force`, `php artisan storage:link`, `php artisan db:seed --force` (re-seeds `products.*`/`orders.*` permissions onto the live `admin` role) all run against the live stack.
- Live HTTP check against the running Apache/MySQL stack, as `genodessa@gmail.com`: created a product (`302`), created a customer (`302`), created an order with 2× that product (price 250 → total `500.00`, `302`), confirmed via `tinker` the customer's `total_orders_amount`/`orders_count`/`completed_orders_count`/`last_order_at` all updated correctly; `/orders` list shows the order with the right customer/status/amount; `/customers/{id}` shows "Надёжность: 100%". All temporary data removed afterward (order/order_items cascade-deleted with the order; product and customer deleted separately).

Tests:
- `php artisan test` — 88/88 passed (8 new Products + 7 new Orders tests), run against the testing environment's SQLite config (unmodified `phpunit.xml`).

Docker:
No image/container changes across both steps.

Problems and resolution:
None specific to Orders/Products beyond routine seed re-runs for the new permission slugs (already handled correctly by the cache-clearing fix from Step 12).

Status:
DONE

Next step:
Awaiting the project owner's next direction. Candidates: Sources/Integrations module (marketplace API keys + order ingestion — would start populating orders automatically instead of manual entry), the future API module noted in `docs/BACKLOG.md`, or stock management for Products/Orders (explicitly deferred in this step's Design notes).

---

## Step 15 — Design revision: sidebar borders/hover, wider layout, 3 new Dashboard cards

Date: 2026-09-04

Goal:
Project owner feedback on the design system: separate the sidebar's logo area from its nav items with a (thicker) border, add a border between individual nav items, add a grey hover background to nav items (previously hover only changed text color); widen the page content area; add three new Dashboard cards (last 5 orders, products below a configurable low-stock threshold, order counts by status), arranged in a responsive grid (2-3 per row) instead of stacked. Two further asks — draggable card reordering, and a settings screen for which cards are visible — are answered/deferred below rather than built, pending the project owner's input (see the reply accompanying this step).

Changed files:
- `resources/views/layouts/app.blade.php` — new tokens `--color-sidebar-border` (`rgba(255,255,255,.14)`) and `--color-sidebar-hover` (`#333333`). `.sidebar-top` gets a `2px` bottom border (the "thicker" logo/menu divider); `.sidebar-link` gets a `1px` bottom border (divider between items) and `background: var(--color-sidebar-hover)` on `:hover`/`.active` (previously text-color-only). `.content-inner` max-width raised `1000px → 1400px`. New `.card-grid` utility (`display:grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem`, with `.card-grid .card { margin-top: 0 }` to avoid double-spacing against the existing stacked-card rule) — applied to the Dashboard only; other pages (Users/Roles/Products/Orders/Customers lists and forms) are unaffected and keep single-column stacking.
- `app/Modules/Dashboard/Services/DashboardService.php` — added `recentOrders()`, `lowStockProducts()` (queries `stock < config('dashboard.low_stock_threshold')`), `orderStatusCounts()` (one grouped query, backfilled with `0` for any status with no orders yet, keyed by `Order::STATUS_LABELS` so the card always shows all four statuses).
- `app/Modules/Dashboard/Controllers/DashboardController.php` — the three new cards' data is only fetched/passed when the current user actually holds `orders.view`/`products.view` (checked in the controller, not just hidden in Blade) — consistent with how every other permission-gated part of the UI works; a user without those permissions gets `null` for that data and the card markup itself is wrapped in a matching `@can` in the view, so nothing renders either way.
- `resources/views/dashboard/index.blade.php` — rebuilt inside `.card-grid`: History card (unchanged), + "Последние 5 заказов" and "Заказы по статусам" (both behind `@can('orders.view')`), + "Мало на складе (< N)" (behind `@can('products.view')`, `N` read from `config('dashboard.low_stock_threshold')` so the page always reflects the actual configured value).
- `tests/Feature/DashboardTest.php` — 3 new tests: a permission-less user sees none of the three new cards; an admin sees recent orders + status counts; an admin sees only the product below the configured threshold (not the one with plenty of stock).

Created files:
- `config/dashboard.php` — `low_stock_threshold` from `DASHBOARD_LOW_STOCK_THRESHOLD` (`.env`/`.env.example`, default `2`) — the project owner explicitly asked for this number to live in configuration, not be hardcoded, matching the existing `AUTH_LOGIN_MAX_ATTEMPTS`-style pattern.

Deleted files:
None.

Dependencies:
None added.

Checks:
- `php artisan test` confirmed no regression: a plain (unseeded-permissions) `User` hitting `/dashboard` does not throw when the controller calls `$user->can('orders.view')`/`can('products.view')` on a database with no `orders.view` permission row at all — Spatie's `checkPermissionTo()` (used internally by Gate's `can()`) catches its own `PermissionDoesNotExist` and returns `false` rather than throwing; confirmed by running the pre-existing `DashboardTest` cases first, before adding the new ones.
- Live HTTP check against the running stack, as `genodessa@gmail.com`: `/dashboard` HTML contains `card-grid`, `sidebar-border`, `sidebar-hover`, and all three new card headings.

Tests:
- `php artisan test` — 91/91 passed (3 new).

Docker:
No image/container changes.

Problems and resolution:
None.

Status:
DONE — for the parts with clear scope. Two items from the same request are explicitly **not** implemented pending the project owner's answer (see the reply sent alongside this step): draggable card reordering, and a "which cards are visible" settings screen. Revisit this log entry once those are scoped.

Next step:
Awaiting the project owner's decision on card-visibility settings (per-user, stored where?) and whether drag-to-reorder is worth building now, per the questions raised in this step's reply.

---

## Step 16 — Settings module: Dashboard card visibility

Date: 2026-09-04

Goal:
Answer to Step 15's open question: the project owner confirmed card visibility is **one global, admin-configured setting** (not per-user). Implements a small generic `settings` key-value store plus a Settings screen to toggle which Dashboard cards are shown. Drag-to-reorder remains explicitly deferred (answered but not built — see the reply accompanying Step 15).

Changed files:
- `config/dashboard.php` — added a `cards` map: canonical card key → `{label, permission}` for all four existing cards. This is the single source of truth both the Settings screen and the Dashboard controller read from, so a future new card only needs an entry here plus its query method.
- `app/Modules/Dashboard/Services/DashboardService.php` — added `enabledCardKeys()` (reads `Setting::get('dashboard.enabled_cards', <all keys>)` — defaults to everything enabled until an admin saves otherwise) and `visibleCardKeysFor(User $user)` (intersects the enabled set with which cards the user actually holds the permission for — the setting is a convenience, permission checks remain the real boundary).
- `app/Modules/Dashboard/Controllers/DashboardController.php` — now computes `visibleCardKeysFor()` once and only queries/passes each card's data when its key is in that list (previously this was permission-only).
- `resources/views/dashboard/index.blade.php` — each card block now gated by `in_array($key, $visibleCards)` instead of `@can`, since "visible" already folds the permission check in.
- `database/seeders/RolePermissionSeeder.php` — added `settings.edit` (single permission — this is one small form, no separate `settings.view`, unlike other modules).
- `resources/views/layouts/app.blade.php` — added a "Settings" sidebar link (new gear-style `settings` icon).

Created files:
- `database/migrations/2026_09_04_100000_create_settings_table.php` — `settings`: `key` (unique string), `value` (nullable JSON).
- `app/Models/Setting.php` — deliberately generic key-value model (`Setting::get($key, $default)` / `Setting::set($key, $value)`), not hardcoded to dashboard cards, so the next admin-editable-without-a-deploy setting doesn't need a new table.
- `app/Modules/Settings/Requests/UpdateDashboardSettingsRequest.php`, `app/Modules/Settings/Services/SettingsService.php` (`enabledDashboardCards()`, `updateDashboardCards()` — the latter also writes a `settings.updated` audit-log entry, same convention as every other mutating Service), `app/Modules/Settings/Controllers/SettingsController.php` (`edit`/`update` — a singleton-style resource, one page), `app/Modules/Settings/routes.php` (`GET`/`PUT /settings`, behind `can:settings.edit`).
- `resources/views/settings/edit.blade.php` — a checkbox per configured card, pre-checked from the current setting.
- `tests/Feature/Settings/SettingsTest.php` — 4 tests: guest redirected, 403 without permission, all cards checked by default (nothing saved yet), saving a subset both persists and actually removes the unchecked card from `/dashboard`.

Deleted files:
None.

Dependencies:
None added.

Checks:
- `php artisan migrate --force`, `php artisan db:seed --force` (adds `settings.edit` to the live `admin` role) run against the live stack.
- Live HTTP check, as `genodessa@gmail.com`: `/settings` shows all 4 card checkboxes checked; submitting the real form with `low_stock_products` unchecked → `/dashboard` no longer shows "Мало на складе" while "Последние 5 заказов" still does; reset back to all four enabled afterward (the app's actual default state) and confirmed.

Tests:
- `php artisan test` — 95/95 passed (4 new).

Docker:
No image/container changes.

Problems and resolution:
None.

Status:
DONE

Next step:
Awaiting the project owner's next direction — draggable card reordering is still on the table if wanted; otherwise Sources/Integrations, the future API module, or stock management remain the open Phase 2 candidates from Step 14.

---

## Step 17 — Settings tabs, editable low-stock threshold, scrollable low-stock card

Date: 2026-09-04

Goal:
Three pieces of feedback on Step 16: (1) the Settings page should be tab-structured, starting with one "Dashboard" tab; (2) the low-stock threshold wasn't editable anywhere in the UI (only via `.env`/`config`) — move it into the same Settings screen; (3) confirm/handle scrolling for the low-stock card once there are many (up to ~100) products.

Changed files:
- `app/Modules/Dashboard/Services/DashboardService.php` — added `LOW_STOCK_THRESHOLD_KEY` constant and `lowStockThreshold(): int` (`Setting::get(..., config('dashboard.low_stock_threshold'))` — the `.env` value is now only the initial default before an admin ever saves one via Settings); `lowStockProducts()` now calls this instead of reading `config()` directly.
- `app/Modules/Dashboard/Controllers/DashboardController.php` — passes `lowStockThreshold` to the view (previously the view read `config()` directly, which would no longer reflect an admin-saved override).
- `resources/views/dashboard/index.blade.php` — low-stock card heading now shows the live threshold; its table is wrapped in a new `.table-scroll` div (`max-height: 320px; overflow-y: auto`, sticky header) so a long list (tested with 15 rows live, designed for up to ~100) scrolls inside the card instead of stretching the grid row.
- `resources/views/layouts/app.blade.php` — added `.tab-nav`/`.tab-btn`/`.tab-panel[hidden]` styles and a small generic tab-switching script (delegated click on any `.tab-nav`, toggles `.active` + sibling `[data-tab-panel]` visibility by matching `data-tab` — written once, reusable for any future tabbed page, not just Settings); added `.table-scroll` styles; added `input[type=number]` to the shared input styling (previously missing, so the new threshold field would've been unstyled).
- `app/Modules/Settings/Requests/UpdateDashboardSettingsRequest.php` — added `low_stock_threshold` (`required|integer|min:0`).
- `app/Modules/Settings/Services/SettingsService.php` — now depends on `DashboardService` directly (rather than duplicating its `Setting::get` calls) for `enabledDashboardCards()`/`lowStockThreshold()`; `updateDashboardCards()` renamed to `updateDashboardSettings(array $cards, int $lowStockThreshold)`, saving both settings and logging one `settings.updated` audit entry for the pair.
- `app/Modules/Settings/Controllers/SettingsController.php` — passes `lowStockThreshold` to the view; `update()` passes both validated fields to the renamed service method.
- `resources/views/settings/edit.blade.php` — rebuilt around a `.tab-nav`/`.tab-panel` pair (one tab, "Dashboard", `data-tab="dashboard"`) so adding a second settings category later is just another button + panel, no restructuring; the existing card checkboxes and the new `low_stock_threshold` number input now live in the same form/tab.
- `tests/Feature/Settings/SettingsTest.php` — updated the existing "disable a card" test to also send the now-required `low_stock_threshold`; added 2 new tests: admin can change the threshold (persists and shows on reload), and the threshold is required (validation).

Created files:
None.

Deleted files:
None.

Dependencies:
None added.

Checks:
- Live HTTP check against the running stack, as `genodessa@gmail.com`: `/settings` HTML contains `tab-nav`/`tab-btn` and the `low_stock_threshold` field; created 15 products with `stock=1` via `tinker`, confirmed all 15 render inside the `.table-scroll` wrapper on `/dashboard` (would scroll rather than break the grid at the ~100-product scale the project owner described). Temporary products removed and log cleared afterward.

Tests:
- `php artisan test` — 97/97 passed (2 new, 1 updated).

Docker:
No image/container changes.

Problems and resolution:
None specific to this step (one live-check `tinker` command using a PHP `for` loop failed to parse due to Bash escaping of `$i` — same known class of issue as prior sessions' shell-escaping gotchas; switched to `Product::factory()->count(15)->create(...)` instead, no code impact).

Status:
DONE

Next step:
Awaiting the project owner's next direction — draggable card reordering remains open if wanted; otherwise Sources/Integrations, the future API module, or stock management remain the open Phase 2 candidates from Step 14.

---

## Step 18 — Products API (Sanctum)

Date: 2026-09-04

Goal:
First real API surface: token-authenticated Products endpoints, so the project owner's external website can push its product catalog into the CRM once deployed. Triggered `DECISIONS.md` #10's condition ("Sanctum once there's an actual API consumer") — see new decision #13.

Changed files:
- `composer.json`/`composer.lock` — added `laravel/sanctum`.
- `bootstrap/app.php` — `withRouting()` now also loads `routes/api.php` (added by `php artisan install:api`).
- `app/Models/User.php` — added Sanctum's `HasApiTokens` trait.

Created files:
- `database/migrations/2026_09_04_151321_create_personal_access_tokens_table.php` (Sanctum's own migration, published by `install:api`).
- `config/sanctum.php` (published default config, untouched).
- `routes/api.php` — mirrors `routes/web.php`'s convention: loops over `app/Modules/*/api-routes.php` and requires each (the installer's placeholder `/user` sample route was removed).
- `app/Modules/Products/api-routes.php` — `GET/POST /api/products`, `GET/PUT /api/products/{product}`, behind `auth:sanctum` then the same `can:products.<action>` middleware the web routes use.
- `app/Modules/Products/Controllers/Api/ProductApiController.php` — `index`/`show`/`store`/`update`, all delegating to the **existing** `ProductService` and reusing the **existing** `StoreProductRequest`/`UpdateProductRequest` (same validation rules as the web form — no duplicated rules). `store()` returns `201`.
- `app/Modules/Products/Resources/ProductResource.php` — JSON shape: `id`, `name`, `sku`, `price` (string), `stock`, `description`, `category`, `photo_url`, `created_at`/`updated_at` (ISO 8601).
- `app/Console/Commands/CreateApiToken.php` — `php artisan api-token:create {email} {name=integration}`, prints a plain-text Sanctum token once. Chosen over a token-management UI screen since this is a server-to-server credential an admin issues rarely, not something needing a full CRUD screen yet.
- `tests/Feature/Api/ProductApiTest.php` — 7 tests: no token → `401`; token without `products.view` → `403`; list; get one; create (`201`, persisted); create validation errors (`422`, same required fields as the web form); update.

Deleted files:
None.

Dependencies:
`laravel/sanctum` (^4.3) — see `DECISIONS.md` #13 for the reasoning.

Design notes:
- Confirmed empirically (via the passing 403 test) that Spatie permission checks work identically for a Sanctum-token-authenticated `User` as for a session-authenticated one — permissions aren't guard-scoped in a way that breaks this, since it's the same `User` model with the same `web`-guard roles/permissions either way.
- API error responses are already JSON, not the HTML error pages — `bootstrap/app.php`'s `shouldRenderJsonWhen()` (added back in Step 7) already covers `api/*` paths, so no additional wiring was needed for 401/403/422/500 on this new surface.
- No `DELETE /api/products/{id}` — matches the original BACKLOG.md ask (add/get/update/list only) and the web UI's own product-delete safeguards weren't re-litigated for the API.

Checks:
- `php artisan route:list --path=api` — confirms all 4 routes.
- Live HTTP check against the running stack: issued a real token via `php artisan api-token:create genodessa@gmail.com website-test`; `GET /api/products` with `Authorization: Bearer <token>` → `200`; `POST /api/products` with a JSON body → `201` with the created product's JSON. Test product and token revoked afterward.

Tests:
- `php artisan test` — 104/104 passed (7 new).

Docker:
No image/container changes.

Problems and resolution:
None.

Status:
DONE

Next step:
Project owner's next question (deployment: how to move this CRM to real hosting so their website can actually reach `/api/products`) is being answered separately — see the reply accompanying this step. Once hosting is confirmed, Orders/Users API endpoints and draggable Dashboard cards remain open candidates.

---

## Session paused after Step 18 — deployment on hold

Date: 2026-09-04

Work paused here at the project owner's request; hosting/deployment is postponed for now ("перенос откладывается на некоторое время"). State to resume from:

- Steps 1-18 all DONE, committed on `main` up to `ca84074`, pushed to `https://github.com/gen2023/crm.git`.
- Phase 1 complete. Phase 2 so far: Customers, Products, Orders, Settings (Dashboard card visibility + low-stock threshold), and a first API surface (Products, Sanctum token auth).
- Stack: `docker-compose up -d` in `crm/`, app at `https://localhost`. Login: `genodessa@gmail.com` / `Genodessa2026!`. API token for testing was created and then revoked during Step 18's live check — issue a fresh one with `php artisan api-token:create <email> <name>` when needed again.
- **Deployment groundwork discussed but not started.** The project owner has a hosting provider in mind (`ukraine.com.ua`, VPS with root access, ~2 CPU/4GB recommended, Ubuntu). When resuming, the reply just before this pause has the full checklist (`.env` changes for production, SSL/Let's Encrypt, removing the public `3306`/`8083` port exposure, etc.). Two concrete open questions from that reply, still unanswered:
  1. Should the outer `crm/` Docker infrastructure (currently **not** in any git repo — see `DECISIONS.md` #11/#12) get its own separate git repository, so deployment is `git clone` + `git clone` instead of manual file copying?
  2. Should a `docs/DEPLOYMENT.md` step-by-step guide be written now (in `cms/docs/`), or wait until the VPS is actually provisioned?
- Also still open from earlier: draggable Dashboard card reordering (answered as "worth doing, moderate effort" but not built — see Step 15/17 discussion), and the Orders/Users API endpoints noted in `docs/BACKLOG.md`.
- No other open questions blocking work; nothing else has been decided.

---

## Step 19 — First production deployment (shared hosting) + password visibility toggle

Date: 2026-09-04

Goal:
The pause above turned out to be short — deployment happened the same day, but via a different path than the pause note anticipated: **shared hosting, not a VPS**. The project owner already had a "Кращий" tier shared-hosting account (ukraine.com.ua) running their Joomla site, and it turned out to have everything needed (SSH, Composer, PHP 8.4 alongside the SSH-default PHP 7.4) — so both open questions from the pause note are moot (no VPS, no separate `crm/` git repo needed — this deployment never touches Docker at all). `docs/DEPLOYMENT.md` was written live, during the actual deployment, reflecting what was really done rather than written speculatively beforehand.

**Target:** `https://crm.healthydriedfood.com`, document root `/home/gen2/healthydriedfood.com/crm/public`, deployed by cloning `cms`'s GitHub repo directly onto the shared host (no Docker there) and running Composer/Artisan via `/usr/local/php84/bin/php` (the SSH session's bare `php`/`composer` resolve to PHP 7.4, which is too old — every command had to use the full PHP 8.4 path explicitly).

Created files:
- `docs/DEPLOYMENT.md` — full step-by-step guide for this exact deployment path (see file for the authoritative command list); kept in sync with what actually happened, including the fixes below.
- `resources/views/components/password-field.blade.php` — new reusable component (`<x-password-field name="..." />`): wraps a `type=password` input with a show/hide toggle button (reuses the existing `eye` icon). Applied to every password field in the app: `auth/login.blade.php`, `auth/reset-password.blade.php`, `users/partials/form.blade.php` (both `password` and `password_confirmation`).

Changed files:
- `resources/views/layouts/app.blade.php` and `resources/views/auth/layout.blade.php` — added matching `.password-field`/`.password-toggle` CSS and a small delegated-click JS handler (toggles `input.type` between `password`/`text`) to **both** shells, since login/reset-password use the separate `auth.layout` shell that doesn't share markup with the authenticated `layouts.app` shell.
- `resources/views/{users,roles,customers,products}/{create,edit}.blade.php` — added `style="margin-top:1.5rem;"` to every form's submit button (previously only `orders/partials/form.blade.php` had this) — the project owner flagged the Users edit form's Save button sitting flush against the roles checkboxes with no breathing room; fixed consistently everywhere the same layout pattern (fields/checkboxes immediately followed by a submit button) occurs, not just on the one screen reported.

Deleted files:
None.

Dependencies:
None added.

Deployment problems hit and resolved (documented here since they're specific, non-obvious gotchas of *this* hosting environment — general steps are in `DEPLOYMENT.md`):
1. `git clone <url> crm` run from *inside* the already-existing `crm/` directory (created by the panel for the subdomain) produced a nested `crm/crm/` — fixed by cloning into `.` instead once the directory's contents were confirmed to be just the panel's placeholder `index.html` + empty `public/` (removed first).
2. Mid-recovery, the shell's working directory itself briefly stopped resolving (`fatal: unable to get current working directory`) — the panel appears to recreate/replace the subdomain folder on some settings saves, invalidating an already-`cd`'d-into shell session. Fixed by reconnecting/`cd`-ing fresh.
3. `DB_HOST=localhost` failed with `SQLSTATE[HY000] [2002] No such file or directory` — PHP's mysqlnd treats `localhost` as "connect via Unix socket," and the socket path built into this PHP 8.4 build didn't match. Switching to the hosting's dedicated MySQL hostname (`gen2.mysql.tools`, found in the panel's database section) resolved the transport-level issue.
4. That then failed with `Access denied ... (using password: YES)` — root cause: the generated database password contained a `#` (`h9Zc;s2#N7`), and `.env` treats unquoted `#` as the start of a comment, silently truncating the value. Fixed by quoting the value: `DB_PASSWORD="h9Zc;s2#N7"`.
5. After that, `No application encryption key has been specified` despite `key:generate` having already run successfully earlier — a `.env` edit made through an IDE's SFTP-backed editor (a stale local buffer opened *before* `key:generate` ran) overwrote the server's `.env` on save, wiping the generated key. Fixed by re-running `artisan key:generate --force` *after* all manual `.env` edits were finished, and flagged the risk of editing the same file through two different tools (IDE SFTP tab vs `nano` over SSH) without reloading between edits.
6. `AdminUserSeeder` correctly skipped in production (`APP_ENV=production`, by design — see `DECISIONS.md`/Step 10) — the first production admin was created by hand via `artisan tinker` (`User::create([...])->assignRole('admin')`), currently with **placeholder name/email/password** ("Ваше имя" / `you@example.com` / a placeholder string) that the project owner intends to change via the Users UI once logged in — noted here so it isn't mistaken for a real credential if seen in a future audit-log entry or admin list.

Checks:
- Full deployment sequence completed successfully end-to-end on the live shared-hosting environment: `composer install --no-dev`, `.env` configured, `artisan key:generate`, `migrate --force` (all 12 migrations ran), `db:seed --force` (roles/permissions seeded, `AdminUserSeeder` skipped as designed), admin user created manually, `storage:link`. Site confirmed reachable and login working at `https://crm.healthydriedfood.com/login` (custom 500 error page was also incidentally confirmed working correctly — no debug info leaked — during the `APP_KEY` troubleshooting).
- Local (dev) verification of the password-toggle/button-spacing fix: `php artisan test` — 104/104 still passing; live `curl` of `/login` confirms `password-field`/`password-toggle` markup present; live `curl` of `/users/create` (as `genodessa@gmail.com`) confirms both the toggle markup and the new button spacing.

Tests:
- `php artisan test` — 104/104 passed (no new tests this step — the two fixes are presentational/CSS+JS, not business logic; existing Feature tests already assert on form submission behavior, which is unaffected by the input markup wrapper).

Docker:
No image/container changes (this step's deployment work happened entirely on the external shared-hosting account, not the local Docker stack; the local stack was only used to verify the password-toggle/spacing fixes before pushing).

Status:
DONE

Next step:
Project owner is signing off for the day. Remaining before the production instance is fully "real": change the placeholder admin's name/email/password via the Users UI, decide on a real mail provider (password-reset emails are still `MAIL_MAILER=log` in production — see `DEPLOYMENT.md`'s "Still open" section), and issue a real API token for the website integration once ready to actually connect it. Also still open: draggable Dashboard cards, Orders/Users API endpoints (`docs/BACKLOG.md`), Sources/Integrations module.
