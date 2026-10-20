# ProManage — Production Deployment Checklist

## 1. Environment (.env)

Copy `.env.example` to `.env` and update:

```env
APP_NAME="ProManage"
APP_ENV=production
APP_KEY=          # php artisan key:generate
APP_DEBUG=false
APP_URL=https://your-domain.com

LOG_LEVEL=warning

# Session — harden for HTTPS
SESSION_DRIVER=database
SESSION_LIFETIME=480
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=strict
```

> **Never deploy with `APP_DEBUG=true`** — stack traces expose file paths, env variables, and internal logic.

## 2. One-time setup commands

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build

php artisan key:generate --force   # if fresh .env
php artisan migrate --force
php artisan db:seed --class=DatabaseSeeder  # first deploy only

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan icons:cache            # if using Blade Icons
```

## 3. File permissions

```bash
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## 4. Security checklist

| Check | Status |
|---|---|
| `APP_DEBUG=false` | Required |
| `APP_ENV=production` | Required |
| `SESSION_ENCRYPT=true` | Required |
| `SESSION_SECURE_COOKIE=true` | Required (HTTPS only) |
| `SESSION_SAME_SITE=strict` | Recommended |
| HTTPS / TLS certificate | Required |
| `storage/` not web-accessible | Required — ensure web root is `public/` |
| `.env` not web-accessible | Required — never put `.env` in `public/` |
| Database backups scheduled | Required |
| Log rotation configured | Recommended |

## 5. Security features already built in

- **Login rate limiting** — 5 failed attempts triggers lockout (per email + IP)
- **Registration rate limiting** — 5 requests per minute per IP
- **Password reset rate limiting** — 3 requests per 5 minutes per IP
- **Security headers** — CSP, X-Frame-Options, X-Content-Type-Options, HSTS (auto-enabled on HTTPS), Referrer-Policy, Permissions-Policy
- **CSRF protection** — all state-changing routes
- **Deactivated user blocking** — blocked at login and mid-session
- **Audit log** — every write action recorded with user + IP
- **Passwords never logged** — filtered from all activity logs
- **BCrypt cost 12** — password hashing

## 6. Cron jobs (Laravel Scheduler)

Add one cron entry on the server:

```cron
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

The scheduler handles:
- `attendances:close-stale` — auto-closes open attendance sessions at midnight

## 7. Queue worker (if using database queue)

```bash
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

Use Supervisor or systemd to keep this running.

## 8. Switching from SQLite to MySQL/PostgreSQL (recommended for production)

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=promanage
DB_USERNAME=promanage_user
DB_PASSWORD=strong_password_here
```

Then run `php artisan migrate:fresh --seed` on the new database.
