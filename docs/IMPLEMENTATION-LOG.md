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
