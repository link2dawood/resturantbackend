# CI/CD Setup

Two workflows live in `.github/workflows/`:

| Workflow | When it runs | What it does |
|---|---|---|
| `ci.yml` | every push and PR to `main` or `coa` | runs the test suite on **MySQL 5.7**, then migrates up, rolls Phase 5 back, migrates up again, and seeds twice to prove idempotency |
| `deploy.yml` | manual only | backs up the database, pulls, migrates, rebuilds caches, health-checks the live site |

CI needs no configuration. Deploy needs nine secrets.

---

## Why MySQL 5.7 in CI

Production runs 5.7. Testing on 8.x would pass while hiding the index-length and
strict-mode differences that only bite on the older server. The local test runs
use SQLite for speed; CI is the run that matches production.

**Expect the first CI run to be informative rather than green.** Sixteen P&L
tests currently skip on SQLite with "requires MySQL". On MySQL they will actually
execute for the first time in this pipeline. If any fail, that is a pre-existing
gap being revealed, not something the pipeline broke.

---

## Deploy secrets

Repo → Settings → Secrets and variables → Actions → New repository secret.

| Secret | Value | Where to find it |
|---|---|---|
| `SSH_HOST` | your Bluehost host | the host you SSH to |
| `SSH_USER` | `hbpfkwmy` | your cPanel user |
| `SSH_PORT` | `22` | cPanel may use a different port |
| `SSH_PRIVATE_KEY` | the full private key, including the BEGIN and END lines | generate below |
| `DEPLOY_PATH` | `~/public_html/stores` | where the app lives |
| `DB_USERNAME` | `hbpfkwmy_adeel` | server `.env` |
| `DB_PASSWORD` | the database password | server `.env` |
| `DB_DATABASE` | `hbpfkwmy_stores` | server `.env` |
| `APP_URL` | `https://stores.fannsphilly.com` | used by the health check |

### Generating a deploy key

Do this on your Mac, not on the server:

```bash
ssh-keygen -t ed25519 -f ~/.ssh/fannsphilly_deploy -C "github-actions-deploy" -N ""
cat ~/.ssh/fannsphilly_deploy.pub
```

Add the **public** key to the server:

```bash
ssh hbpfkwmy@your-host "mkdir -p ~/.ssh && chmod 700 ~/.ssh && echo 'PASTE_PUBLIC_KEY_HERE' >> ~/.ssh/authorized_keys && chmod 600 ~/.ssh/authorized_keys"
```

Put the **private** key in the `SSH_PRIVATE_KEY` secret:

```bash
cat ~/.ssh/fannsphilly_deploy
```

Verify it works before trusting the pipeline:

```bash
ssh -i ~/.ssh/fannsphilly_deploy hbpfkwmy@your-host "cd ~/public_html/stores && git branch --show-current"
```

### Requiring approval before a deploy

Repo → Settings → Environments → New environment → name it `production` →
tick **Required reviewers** and add yourself. The deploy then pauses for a click
before it touches the live system.

---

## Running a deploy

Actions tab → **Deploy** → Run workflow.

| Input | Default | Meaning |
|---|---|---|
| `branch` | `coa` | which branch to deploy; production tracks `coa` |
| `run_migrations` | true | untick for a code-only release |
| `dry_run` | false | tick to see the incoming commits and file changes without pulling anything |
| `seed_reference_data` | false | re-runs the vendor, category and order-guide seeders; all idempotent |

**Do a dry run first.** It prints the commits and changed files that would be
applied, and writes nothing.

### What a real run does, in order

1. Prints the branch and commit currently live
2. Backs up the database, then **fails the deploy if the dump does not end with `Dump completed`**
3. Deletes backups older than 30 days
4. Shows the incoming commits and changed files
5. `git pull`
6. Runs `composer install` **only if `composer.lock` changed**, fetching `composer.phar` first since the server has no composer on its PATH
7. `migrate --pretend`, then `migrate --force`
8. Clears and rebuilds the route, config, view and application caches
9. Optionally seeds reference data
10. Health-checks `/login` and `/`, failing if either returns an unexpected status or renders `Whoops` / `SQLSTATE`
11. Reports the commit now live

---

## What it deliberately does not do

- **No `migrate:fresh`, no `db:wipe`.** Nothing in this pipeline can drop a table.
- **No automatic deploy on merge.** The push trigger is commented out in `deploy.yml`. Turn it on only once you have watched several manual runs behave.
- **No sample-data seeders.** `InventoryItemsSeeder`, `VendorPricesSeeder` and `StoreInventoryTargetsSeeder` are development fixtures and are never run against production.
- **No asset build.** `public/css` and `public/js` are committed, so there is no npm step. If you ever move to Vite, add `npm ci && npm run build` to CI and rsync `public/build`.

---

## If a deploy fails

The database backup is taken before anything changes, and its filename is printed
in the job log.

```bash
ssh hbpfkwmy@your-host
cd ~/public_html/stores
ls -t ~/deploy-backup-*.sql | head -3
```

Roll the schema back:

```bash
php artisan migrate:rollback --step=10
```

Or restore fully:

```bash
mysql -u hbpfkwmy_adeel -p hbpfkwmy_stores < ~/deploy-backup-YYYYMMDD-HHMMSS.sql
git reset --hard <previous-commit>
php artisan route:clear && php artisan config:clear && php artisan view:clear
```

---

## Cron on the server, still manual

The pipeline does not manage cron. These two entries are what make the weekly
workflow actually fire, and they need adding by hand once:

```
* * * * * cd ~/public_html/stores && php artisan schedule:run >> /dev/null 2>&1
```

A queue worker is only needed if `QUEUE_CONNECTION` is not `sync`. It is
currently `sync`, so notifications send inline and no worker is required.
