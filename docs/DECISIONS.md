# CRM — Architecture Decision Log

This file records decisions with architectural significance only. Routine implementation details belong in `IMPLEMENTATION-LOG.md`, not here.

Each entry: date, decision, context/reason, consequences.

---

## 2026-09-03 — 1. Laravel instead of a custom PHP/MVC micro-framework

**Decision:** Build the CRM on Laravel rather than a hand-rolled PHP MVC framework.

**Context:** The initial Phase 1 spec described Core components (Router, Request/Response, Session, Auth, Validation, Exceptions, Logging) that read like a request to build a framework from scratch. The project owner explicitly rejected that direction in favor of Laravel.

**Consequences:** All Core-level concerns (routing, request/response, sessions, validation, exceptions, logging, CSRF, rate limiting) are provided natively by Laravel and must not be reimplemented. Custom code is limited to actual business logic and the thin module/AuditLogger conventions described in `ARCHITECTURE.md`.

---

## 2026-09-03 — 2. Laravel 13 as the initial major version

**Decision:** Target Laravel 13 for the initial implementation.

**Context:** Explicit project owner instruction.

**Consequences:** PHP 8.3+ is required (see decision 3). A future major-version upgrade is a separate maintenance task per the versioning policy in `ARCHITECTURE.md`, never bundled with feature work.

---

## 2026-09-03 — 3. PHP 8.3+ as the minimum PHP version

**Decision:** Require PHP 8.3 or newer.

**Context:** Required by the Laravel 13 target (decision 2).

**Consequences:** The Docker app image's PHP version and extensions must be verified/updated to meet this floor as part of Step 1.

---

## 2026-09-03 — 4. spatie/laravel-permission for roles & permissions

**Decision:** Use the `spatie/laravel-permission` package rather than a hand-written roles/permissions schema and service layer.

**Context:** The functional requirements (multiple roles per user, DB-stored permissions, a unified `can('permission')` check integrated with Laravel Gate) are exactly what this widely-used, actively maintained package provides. Considered and rejected: custom `role_permissions`/`user_roles` tables with a custom `PermissionService` — more control over exact table names, but reinvents a well-solved problem.

**Consequences:** Permission tables follow Spatie's schema (`roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`) instead of the originally sketched `role_permissions`/`user_roles` names. Permission checks use `$user->can()`/`@can`/`can:` middleware throughout.

---

## 2026-09-03 — 5. Modules as plain folders under `app/Modules`, no module package

**Decision:** Organize business modules (`Auth`, `Users`, `Roles`, and future `Customers`/`Products`/`Orders`/`Integrations`/`Reports`) as plain folders under `app/Modules/<Name>/`, without adopting a module-management package such as `nwidart/laravel-modules`.

**Context:** Considered `nwidart/laravel-modules` for stronger isolation (each module as a self-contained mini-package with its own routes/migrations/views/providers). Rejected in favor of a lighter-weight convention to avoid an extra dependency and its own conventions layered on top of Laravel's.

**Consequences:** Migrations, views, and tests stay in Laravel's standard directories rather than nested per-module. Route files per module are auto-discovered via `glob()` from `routes/web.php`. Adding a new module never requires touching this discovery mechanism.

---

## 2026-09-03 — 6. No Repository/DAO layer

**Decision:** Services use Eloquent models directly. No `UserRepository`/`RoleRepository`/`PermissionRepository` classes.

**Context:** The original Phase 1 spec explicitly called for a Repository/DAO layer between Services and the database. The project owner chose to drop it in favor of the more idiomatic Laravel pattern (Eloquent models already provide a data-access abstraction), accepting the reduced indirection as a deliberate trade-off against the letter of the original spec.

**Consequences:** `Controller → Service → Eloquent Model` is the fixed data-flow for Phase 1. Introducing a Repository layer later requires a new decision entry here — it must not be reintroduced piecemeal.

---

## 2026-09-03 — 7. Controller → Service → Eloquent Model as the fixed layering

**Decision:** Controllers stay thin (FormRequest validation + one Service call + response). All business logic lives in Services. No SQL and no multi-step business logic in controllers.

**Context:** Direct requirement from the project owner, consistent with decision 6.

**Consequences:** Code review / self-review for every step checks that controllers have not grown business logic, and that Eloquent queries beyond trivial reads happen inside Services.

---

## 2026-09-03 — 8. Custom minimal AuditLogger instead of spatie/laravel-activitylog

**Decision:** Implement audit logging as a hand-written `audit_logs` table + `App\Support\AuditLogger` service, called explicitly from Services at specific mutation points.

**Context:** Considered `spatie/laravel-activitylog` (automatic model-change tracking via a trait). Rejected for Phase 1 to avoid an extra dependency and an implicit/automatic logging surface, in favor of an explicit, minimal mechanism that still satisfies "lay down the architecture now, without building a full audit UI yet."

**Consequences:** `AuditLogger` calls must be added deliberately wherever a significant change happens; nothing is captured automatically. This is easier to reason about but requires discipline as new modules are added.

---

## 2026-09-03 — 9. Native Laravel Auth/Password instead of custom auth logic

**Decision:** Authentication and password reset are built on Laravel's native `Auth` facade and `Password` broker, wrapped by thin `AuthService`/`PasswordResetService` classes — no custom session/token machinery.

**Context:** Laravel's built-in mechanisms already satisfy the functional requirements (session-based auth with remember-me, session regeneration, hashed/expiring/single-use reset tokens) without custom code.

**Consequences:** No custom `Core/Auth` or token-generation code is written. Any token/session behavior change should first be checked against Laravel's existing configuration options before writing custom logic.

---

## 2026-09-03 — 10. No Breeze/Jetstream/Fortify (or Sanctum/Passport without need)

**Decision:** Do not install `laravel/breeze`, `laravel/jetstream`, `laravel/fortify`, or `laravel/ui`. Do not install `laravel/sanctum` or `laravel/passport` until an actual API consumer exists.

**Context:** These scaffolding packages ship their own controllers/actions that would conflict with the required thin-controller/Service architecture (decisions 6-7), or (for Sanctum/Passport) address a need — an API — that Phase 1 does not have.

**Consequences:** Auth screens/controllers are hand-written against plain Laravel primitives. Introducing any of these packages later requires a new decision entry.

---

## 2026-09-03 — 11. Git root and Laravel root = `/cms`

**Decision:** The application's git repository root is `/cms`, which is also the Laravel application root (`composer.json`, `app/`, `vendor/`, `.env`). `cms/public/` remains Laravel's standard `public/` folder — the only directory exposed to the web via Apache.

**Context:** Briefly discussed alternative — git root at `/cms/public` — was rejected: it would place the entire Laravel application (including `.env` and `vendor/`) inside the web-exposed directory, which is both non-standard and a security risk, and would require reworking the already-agreed Docker `DocumentRoot`/volume configuration.

**Consequences:** All application code, docs (`cms/docs/`), and Laravel internals live under `/cms`. The outer `crm/` directory (Docker/infrastructure) stays a separate, non-application repository.

---

## 2026-09-03 — 12. Docker as external infrastructure, not an architecture driver

**Decision:** The existing Docker setup (`crm/docker-compose.yml`, `crm/docker/app/Dockerfile`, Apache vhost) is treated as external infrastructure that gets adapted to what Laravel needs (PHP 8.3+, Composer, required extensions, `DocumentRoot=/var/www/public`), rather than something the application architecture is designed around.

**Context:** The outer `crm/` directory is not part of the application's git repository (decision 11) and was originally built as a generic PHP/Apache/MySQL scaffold predating the Laravel decision.

**Consequences:** Docker/infrastructure changes (base image version, installed PHP extensions, Composer availability, entrypoint behavior) are made as needed to support the approved application architecture, and are logged in `IMPLEMENTATION-LOG.md` as implementation detail rather than in this decision log, unless they themselves become architecturally significant.

---

## 2026-09-04 — 13. Laravel Sanctum installed — API now has a real consumer

**Decision:** Install `laravel/sanctum` and expose a token-authenticated JSON API, starting with Products (`GET/POST /api/products`, `GET/PUT /api/products/{id}`), reusing the existing `ProductService` and its web `StoreProductRequest`/`UpdateProductRequest` validation rules.

**Context:** Decision 10 explicitly deferred Sanctum "until an actual API consumer exists." The project owner now has a real one: their own website needs to push its product/order catalog into the CRM, which requires the CRM to expose an authenticated API reachable from outside `localhost`. Tokens are issued via a new `php artisan api-token:create {email}` command (Sanctum personal access tokens, no login/session flow needed since this is a server-to-server integration, not a browser client) rather than a token-management UI, to keep scope to what's actually needed right now.

**Consequences:** `routes/api.php` now auto-discovers `app/Modules/*/api-routes.php` (mirroring the existing `routes/web.php` module-discovery convention). API routes are gated the same way as web routes — `auth:sanctum` for authentication, then the same `can:products.*` Gate/Spatie permission middleware — so a token is tied to a real `User` with real roles/permissions, not a separate authorization system. `User` now has the `HasApiTokens` trait. Every future module that gets an API surface should follow this same pattern (its own `api-routes.php`, reusing its existing web Service/Requests) rather than duplicating business logic, per `ARCHITECTURE.md`'s "Web Controller → Service → Model / API Controller → Service → Model" note.
