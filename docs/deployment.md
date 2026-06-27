# Auto Deployment (GitHub Actions → SSH)

Pushing to **`coa`** triggers `.github/workflows/deploy.yml`, which SSHes into the
production server and updates the app. Manual runs: Actions tab → **Deploy** → *Run workflow*.

- Host: `162.241.226.193`  •  Port: `22`  •  User: `hbpfkwmy`
- Branch deployed: `coa`
- **Migrations are NOT run automatically** (your data stays safe). Run them yourself when ready:
  `php artisan migrate --force`  — never `migrate:fresh`/`migrate:refresh` in production.

## One-time setup

### 1. GitHub secret (required)
Repo → **Settings → Secrets and variables → Actions → Secrets** → New secret:

| Name | Value |
|------|-------|
| `DEPLOY_SSH_KEY` | The **private** SSH key (PEM). Its **public** key must be in the server's `~/.ssh/authorized_keys` for `hbpfkwmy`. |

### 2. GitHub variables (optional — only if defaults are wrong)
Repo → **Settings → Secrets and variables → Actions → Variables**:

| Name | Default | Set if… |
|------|---------|---------|
| `DEPLOY_PATH` | `/home/hbpfkwmy/public_html` | the Laravel app lives elsewhere on the server |
| `PHP_BINARY` | `php` | cPanel needs a specific PHP, e.g. `/opt/cpanel/ea-php82/root/usr/bin/php` |
| `COMPOSER_BINARY` | `composer` | composer isn't on PATH, e.g. `/usr/local/bin/composer` |

### 3. Server prerequisites (one-time, on the server)
- The repo is a **git checkout** at `DEPLOY_PATH` (`git clone … .` once), with a remote it can `git fetch` (deploy key/token for a private repo).
- A `.env` exists at `DEPLOY_PATH` (it's gitignored, so deploys never touch it).
- `composer` and a PHP 8.2+ binary are available (see variables above).

## What each deploy does
1. `php artisan down` (maintenance mode)
2. `git fetch` + `git reset --hard origin/coa`
3. `composer install --no-dev --optimize-autoloader`
4. Build assets **if** Node is present (else assumes compiled assets are committed)
5. `config:cache` / `route:cache` / `view:cache`, `storage:link`, `queue:restart`
6. `php artisan up` (back online)

If any step fails the deploy stops **in maintenance mode** (so a broken build isn't served) and the Actions run is marked failed.
