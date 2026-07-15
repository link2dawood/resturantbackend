# Deployment (manual, over SSH)

There is **no CI/CD pipeline** — deploys are run by hand on the production server.

- Host: `162.241.226.193` • Port: `22` • User: `hbpfkwmy`
- App path: `~/public_html/stores`
- Branch deployed: `coa`

## Deploy

```bash
ssh hbpfkwmy@162.241.226.193
cd ~/public_html/stores

git fetch origin && git reset --hard origin/coa

# Only when the release adds/changes columns. Additive migrations only —
# NEVER run migrate:fresh / migrate:refresh: they would drop live data.
php artisan migrate --force

php artisan view:clear
php artisan optimize:clear
```

Hard-refresh the browser (Cmd/Ctrl+Shift+R) if the release touched CSS/JS.

## One-time server setup

```bash
php artisan storage:link   # serves uploaded avatars/logos from storage/app/public
```

## After changing `.env`

The server's `.env` (`~/public_html/stores/.env`) is separate from any local file.
Config is cached, so changes don't take effect until:

```bash
php artisan config:clear
```

## Troubleshooting

| Symptom | Fix |
|---|---|
| 500 right after a deploy | `php artisan view:clear` — a stale compiled Blade view is cached |
| `Class ... not found` after `composer install` | `rm -f bootstrap/cache/packages.php bootstrap/cache/services.php bootstrap/cache/config.php && php artisan package:discover --ansi` |
| Uploaded logo/avatar 404s | `php artisan storage:link` |
| Email not sending | `php artisan mail:test you@example.com` — prints the effective mail config, queue state, and the exact SMTP error |
