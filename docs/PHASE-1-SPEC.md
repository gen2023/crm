# CRM — Phase 1 Specification

Status: APPROVED
Version: 1.0
Date: 2026-09-03

Phase 1 builds the foundation of the CRM: MVC/modular architecture on Laravel, database connectivity, user authentication, user management, roles, permissions, baseline application security, a unified error-handling mechanism, and a basic admin interface.

**Out of scope for Phase 1:** Customers, Products, Orders, Integrations, Reports, and any external integrations. These are named only to make sure Phase 1's architecture does not block them later (see "Future Expansion Contract").

Implementation approach for Phase 1 (Laravel 13 / PHP 8.3+) is defined in `ARCHITECTURE.md`. This document defines *what* must exist; `ARCHITECTURE.md` defines *how* it is structured.

## Foundation

- Laravel 13 application rooted at `/cms` (git root = Laravel root).
- `app/Modules/{Auth,Users,Roles}` as the initial set of business modules, each with `Controllers/`, `Services/`, `Requests/`, `Policies/`, `routes.php`.
- Controller → Service → Eloquent Model, no Repository layer (see `ARCHITECTURE.md`).
- Unified error handling for HTTP 400/401/403/404/422/500.
- Unified permission check via Laravel Gate (`can('permission.slug')`), backed by `spatie/laravel-permission`.

## Docker

- Existing external Docker infrastructure (`crm/docker-compose.yml`, `crm/docker/app/Dockerfile`, Apache vhost) is adapted to run Laravel — Docker does not dictate application structure.
- PHP 8.3+, Composer available in the app image, MySQL 8.0 service already present (`db`), `DocumentRoot=/var/www/public` already matches Laravel's `public/` folder via the `./cms:/var/www` bind mount.

## Authentication

- `/login` — email, password, remember_me. On success: authenticated session, session ID regenerated, `last_login_at` recorded, redirect into the CRM.
- On failure: a single generic error message that does not reveal whether the email exists.
- `/logout` — destroys/invalidates the session, clears auth data, redirects to `/login`. Only reachable when authenticated.
- All admin pages (`/users`, `/roles`, `/dashboard`, ...) are protected by Laravel's `auth` middleware; unauthenticated access redirects to `/login`.

## Password Reset

- `/forgot-password` (email) → `/reset-password` (password, password_confirmation, token), using Laravel's native `Password` broker: random token, hashed at rest, time-limited, single-use, invalidated after successful reset.
- Password policy: minimum length, confirmation required, secure hashing (`Hash`/bcrypt via Laravel), old plaintext password never accessible.

## Users

- Section `/users`: list, view, create, edit, change status, assign roles, deactivate.
- Fields shown: Name, Email, Roles, Status, Created at, Last login.
- Status: `active` / `inactive`. Deactivation is the default path — no hard delete as the primary flow (history is preserved for future audit/reporting needs).
- Admin-created users can log in immediately after creation.
- Editing email re-validates uniqueness.
- Self-protection rules (enforced in `UserService`/`UserPolicy`):
  - a user cannot delete or deactivate their own account;
  - the last remaining administrator cannot be deleted, deactivated, or stripped of the permissions that make them an administrator.

## Roles

- Section `/roles`: list, create, edit, view, delete, assign permissions.
- Fields: Name, Slug, Description, Permissions.
- A user can hold multiple roles simultaneously.

## Permissions

- Format `module.action` (`users.view`, `users.create`, `users.edit`, `users.delete`, `roles.view`, `roles.create`, `roles.edit`, `roles.delete`, and later `customers.*`, `products.*`, `orders.*`, ...).
- Permissions are stored in the database (via `spatie/laravel-permission`), never hardcoded as `if (role === 'admin')` branches.
- Controllers/routes use Gate (`can:permission.slug`); UI (sidebar) hides sections the current user cannot access, but this is a UX convenience only — the backend always re-checks the permission.

## User Status

- `active` / `inactive` drives login eligibility and list filtering. No physical delete as the default lifecycle action.

## Audit Log

- Minimal architecture laid down now (see `ARCHITECTURE.md`): `audit_logs` table + `AuditLogger` service, invoked from `UserService`/`RoleService` at key mutation points (create/update/deactivate/role change). Full-featured audit UI/reporting is out of scope for Phase 1.

## Dashboard

- `/dashboard`: welcome message, current user, their role(s), last login. No analytics in Phase 1.

## Validation

- Server-side only, via Laravel `FormRequest` + native validation rules: `required`, `email`, `min`, `max`, `unique`, `confirmed`. Errors rendered next to the relevant field in Blade views.

## Error Handling

- 401 — not authenticated. 403 — authenticated but missing permission. 404 — not found. 422 — validation failure. 500 — internal error.
- No stack traces or internal details shown to the user when `APP_DEBUG=false`.

## UI

- Base admin layout: Header / Sidebar / Content.
- Sidebar entries are shown only for permissions the current user holds (e.g. no "Users" entry without `users.view`) — enforced additionally, and authoritatively, on the backend.

## Security

- CSRF protection on all state-changing requests (POST/PUT/PATCH/DELETE) — Laravel's built-in `VerifyCsrfToken`.
- Session security: HttpOnly cookie, Secure under HTTPS, SameSite, session ID regeneration on login, proper invalidation on logout.
- Rate limiting (brute-force protection) on `/login`, `/forgot-password`, `/reset-password` via Laravel's `throttle:` middleware; concrete limits live in configuration, not hardcoded.
- No secrets/credentials hardcoded in source — environment variables (`.env`) for database, `APP_KEY`, mail, session, `APP_ENV`, `APP_DEBUG`.

## Testing

Minimum required Feature/Unit tests for Phase 1:
- **Auth:** successful login; wrong password; non-existent email; logout; access to a protected route without authentication.
- **Password reset:** request reset; token generation; expired token; token reuse; successful password change.
- **Users:** creation; editing; email uniqueness; deactivation; role assignment.
- **Permissions:** access allowed with the permission; access denied without it; middleware returns 403 correctly.

## Seeders

- Roles: Admin, Manager, User (names/count must remain easy to change — not hardcoded to exactly three).
- Corresponding permissions for the modules present in Phase 1.
- One demo administrator account for local development. Demo credentials must never reach production configuration.

## Definition of Done (Phase 1)

- Application boots; database connects; migrations run; seeders run.
- A user can log in and log out.
- Protected pages are unreachable without authentication.
- Password reset and password change work end-to-end.
- An administrator can create, edit, and deactivate users.
- Roles and permissions exist and are enforced end-to-end (including for middleware).
- Last-administrator protection is enforced.
- CSRF protection, rate limiting, validation, and unified error handling are all in place.
- Logging is in place (application errors, auth failures, failed logins, password resets, significant user changes) — without ever logging passwords, tokens, or session secrets.
- Core tests listed above pass.
- Code follows the approved Controller → Service → Eloquent Model / modular layout; no business logic or SQL lives in controllers.
- No passwords or secrets are committed to source control.

## Future Expansion Contract

Phase 1's architecture must allow the following to be added later **without restructuring what already exists**:

- New business modules — `Customers`, `Products`, `Orders`, `Integrations`, `Reports` — each as its own `app/Modules/<Name>/` following the same Controller/Service/Requests/Policies/routes.php shape.
- New permission slugs for those modules, added purely as seed data.
- A full-featured audit log UI/reporting layer, built on top of the existing `audit_logs` table without changing its shape.
- An API surface (Web Controller → Service → Model and API Controller → Service → Model sharing the same Services), added only when an actual API consumer exists — not built speculatively now.

## Current implementation plan

Implementation proceeds in small, sequentially-approved steps (see `IMPLEMENTATION-LOG.md` for the running record and `DECISIONS.md` for architectural decisions). The first step is:

- **Step 1 — Laravel skeleton + Docker integration**: bring up a clean, unmodified Laravel 13 application inside the existing Docker/Apache/MySQL setup, with no Auth/Users/Roles/Permissions/Audit/Dashboard business logic yet.

Subsequent steps (Auth, Users, Roles/Permissions, Password Reset, Audit Log, Dashboard, hardening, tests) are planned and approved one at a time, per `ARCHITECTURE.md`'s workflow rules.
