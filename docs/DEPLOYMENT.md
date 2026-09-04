# CRM — Deployment (production, shared hosting)

Status: first deployment in progress (2026-09-04)
Target: `https://crm.healthydriedfood.com`, shared hosting at ukraine.com.ua (no Docker, no root)

This documents the **actual** deployment path chosen: direct deployment on existing shared hosting (tariff "Кращий"), not a VPS — confirmed viable because the account has SSH, Composer, and PHP 8.4 available (at `/usr/local/php84/bin/php`; the SSH default `php`/`composer` resolve to PHP 7.4 and must not be used). No Docker on this host — `docker-compose`/`crm/docker/` are dev-only and irrelevant here.

## One-time setup already done

- Subdomain created in the hosting panel: `crm.healthydriedfood.com`, document root `/home/gen2/healthydriedfood.com/crm/public` (must be `.../crm/public`, not `.../crm/` — only Laravel's `public/` folder may be web-exposed, same reasoning as `DECISIONS.md` #11).
- A separate MySQL database created for the CRM (distinct from the Joomla site's database).

## Deployment steps

Run everything below over SSH, from `/home/gen2/healthydriedfood.com/`. Every `php`/`composer` invocation uses the full PHP 8.4 path — the bare `php`/`composer` commands on this account default to PHP 7.4.

```bash
# 1. Clone the app directly into the subdomain's folder (note: the doc root
#    is crm/public, one level inside this clone).
git clone https://github.com/gen2023/crm.git crm
cd crm

# 2. Install dependencies (production: no dev packages, optimized autoloader)
/usr/local/php84/bin/php /usr/local/bin/composer install --no-dev --optimize-autoloader --no-interaction

# 3. Environment file
cp .env.example .env
```

Edit `.env` (via SSH editor, e.g. `nano .env`, or the panel's file manager) and set:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://crm.healthydriedfood.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=<the database name created in the panel>
DB_USERNAME=<its user>
DB_PASSWORD=<its password>

SESSION_SECURE_COOKIE=true
```

Leave `MAIL_MAILER=log` for now (password-reset emails won't actually send — revisit once a real mail provider is decided; not blocking for the Products API use case).

```bash
# 4. App key (generates a fresh one — never reuse the local dev APP_KEY)
/usr/local/php84/bin/php artisan key:generate

# 5. Migrate
/usr/local/php84/bin/php artisan migrate --force

# 6. Seed roles & permissions (AdminUserSeeder will print a warning and
#    skip — it only ever runs when APP_ENV=local, deliberately, so a
#    production seed can never produce a guessable-password admin account.
#    Create the first production admin manually, next step.)
/usr/local/php84/bin/php artisan db:seed --force

# 7. Create the first admin user by hand
/usr/local/php84/bin/php artisan tinker
```
Inside `tinker`:
```php
$user = App\Models\User::create([
    'name' => 'Your Name',
    'email' => 'you@example.com',
    'password' => 'a-strong-unique-password',
    'status' => 'active',
]);
$user->assignRole('admin');
exit
```

```bash
# 8. Storage symlink (needed for uploaded product photos to be web-reachable)
/usr/local/php84/bin/php artisan storage:link

# 9. Production caches (skip these while still actively debugging — every
#    .env/route/view change afterward needs the matching *:cache command
#    re-run, or a `artisan optimize:clear`, to take effect)
/usr/local/php84/bin/php artisan config:cache
/usr/local/php84/bin/php artisan route:cache
/usr/local/php84/bin/php artisan view:cache
```

## Before it actually works end-to-end

- **PHP version for the live site**: confirm in the hosting panel (not just SSH) that the `crm.healthydriedfood.com` subdomain itself is set to run PHP 8.4 — the panel's per-site PHP selector is what actually serves web requests, separate from the SSH CLI default (which stays 7.4 regardless).
- **SSL**: the subdomain needs HTTPS before login will work correctly (`SESSION_SECURE_COOKIE=true` means the session cookie is only sent over HTTPS). Check the panel for AutoSSL / a free Let's Encrypt option for the new subdomain.
- **File permissions**: `storage/` and `bootstrap/cache/` must be writable by the PHP process. Usually fine automatically (files created by the same SSH user PHP-FPM runs as on this kind of hosting); if you hit "permission denied" writing logs/cache, `chmod -R 775 storage bootstrap/cache`.

## Still open / not done yet

- Real mail provider for password-reset emails (currently `log`, per above).
- API token for the actual website integration: once the site is live, issue it with `/usr/local/php84/bin/php artisan api-token:create <email> <name>` (see `DECISIONS.md` #13) — don't reuse the local dev token.
- No automated deploy (git pull + composer install + migrate) yet — each update is manual SSH for now. Worth revisiting once the initial deploy is confirmed working.
- **The API must be publicly reachable** (2026-09-04, project owner's note): `/api/*` on `crm.healthydriedfood.com` needs to actually be callable from the public internet (their website's server will be calling it) — before wiring up the real integration, verify nothing blocks that: no IP allowlist/"under construction" restriction on the subdomain in the panel, no firewall rule limiting access, SSL actually valid for the subdomain (see above). Sanctum's `auth:sanctum` token check is the intended access control here — that's enough on its own; the subdomain itself should not additionally be locked down to specific IPs, or the website's requests will be blocked before they ever reach Laravel.
