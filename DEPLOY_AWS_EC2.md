# Deploying the Coffee POS (Laravel 11) to AWS EC2

This guide deploys the Laravel **Coffee POS** application onto a single AWS EC2
instance. Push to `main` afterwards triggers a GitHub Actions workflow that
automatically rebuilds the frontend, ships the code to the server and runs the
artisan maintenance steps, so the live site always reflects the latest `main`.

## Reference architecture

```
                  push to main
                         │
              GitHub Actions (ubuntu-latest)
                ├─ composer install --no-dev
                ├─ npm run build  (public/build)
                └─ rsync over SSH  ────────────────┐
                                                   ▼
        AWS EC2 (Ubuntu 24.04)  ────────────  /var/www/coffeepos
           ├─ PHP 8.3-FPM             Nginx ──► /var/www/coffeepos/public
           ├─ Composer 2
           ├─ MySQL 8.0  (database: coffeepos)
           └─ cron - php artisan schedule:run
```

Notes for this project that matter during deploy:

| Fact | Consequence |
|---|---|
| MariaDB/MySQL is the real database (`DB_DATABASE=coffeepos`) | set up MySQL and run `migrate` once on the server |
| Sessions, cache and queue all use the `database` driver | **no Redis required**; beware MySQL locks under load |
| Frontend is Vite + Tailwind | `public/build` is git-ignored → assets must be built (GitHub Actions does this) |
| Admins/users upload images into `public/productImages`, `public/adminProfile`, `public/customerProfile` | these are **data, not code** — excluded from rsync `--delete` so uploads survive deploys |
| 🔴 **Never run `php artisan test` against the live DB** — it wipes business data | the deploy workflow does NOT run tests (see AGENTS.md); test locally only |

## 0. Things you need beforehand

- An AWS account with a key pair (e.g. `coffeepos-key.pem`) downloadable from EC2
- A GitHub repository containing this code
- Optional but recommended: a domain name pointing to the instance (A record to
  the EC2 public IP). You can deploy on a raw IP first and add the domain later.

Throughout the guide replace these placeholders:

| Placeholder | Example | Meaning |
|---|---|---|
| `coffeepos-key.pem` | `coffeepos-key.pem` | EC2 key pair file |
| `10.0.0.123` | `54.236.1.200` | EC2 public IPv4 |
| `pos.example.com` | `pos.example.com` | your domain (optional) |
| `/var/www/coffeepos` | — | app directory on the server (fixed path used by the workflow) |
| `my-github-name/coffeepos` | — | your GitHub repository |

---

## 1. Launch the EC2 instance

1. Console ▸ **EC2** ▸ **Launch instance**:
   - Name: `coffeepos`
   - AMI: **Ubuntu Server 24.04 LTS (HVM)** — free tier eligible
   - Instance type: `t3.micro` (or `t2.micro`); 2 GB for a little more headroom
   - Key pair: **Create a new key pair** → `coffeepos-key` → RSA → save the
     `coffeepos-key.pem` file somewhere safe (you'll upload it to GitHub)
2. **Network settings ▸ Edit** and create/select a security group named
   `coffeepos-sg` with these inbound rules:

   | Type | Protocol | Port | Source |
   |---|---|---|---|
   | SSH | TCP | 22 | `0.0.0.0/0` (consider restricting to your IP) |
   | HTTP | TCP | 80 | `0.0.0.0/0` |
   | HTTPS | TCP | 443 | `0.0.0.0/0` (if you use a domain + certificate) |

3. Launch, wait for **Running**, then note the **Public IPv4** address.

> If you use a domain: create an **A record** at your DNS provider pointing the
> hostname at the instance's public IP before starting this guide, so the
> certificate step can verify it.

---

## 2. First login to the server

```bash
chmod 400 coffeepos-key.pem
ssh -i coffeepos-key.pem ubuntu@10.0.0.123
```

You are now root-ish via `sudo` (the `ubuntu` user has passwordless `sudo`,
which the GitHub Actions workflow relies on).

---

## 3. Install the stack (PHP 8.3, Nginx, MySQL, Composer)

Run all of this **on the server** under `ubuntu`:

```bash
sudo apt update && sudo apt full-upgrade -y

# Optional: 2 GB swap so composer/php don't run out of RAM on small instances
sudo fallocate -l 2G /swapfile && sudo chmod 600 /swapfile
sudo mkswap /swapfile && sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab

# PHP 8.3 + the extensions Laravel needs (incl. MySQL, Zip for composer)
sudo apt install -y software-properties-common
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update
sudo apt install -y \
  php8.3-fpm php8.3-cli php8.3-mysql \
  php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip \
  php8.3-gd php8.3-bcmath php8.3-intl \
  nginx mysql-server git unzip curl

# Composer 2
curl -sS https://getcomposer.org/installer -o composer-setup.php
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm composer-setup.php
composer --version

# (Optional but recommended) base hardening
sudo apt install -y unattended-upgrades && sudo dpkg-reconfigure -plow unattended-upgrades
```

---

## 4. Create the MySQL database and user

MySQL on Ubuntu binds to a socket with auth on `root`:

```bash
sudo mysql
```

Inside the MySQL shell, create a dedicated app user **(replace the password)**:

```sql
CREATE DATABASE IF NOT EXISTS coffeepos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'coffeepos'@'localhost' IDENTIFIED BY 'A-strong-app-password-here';
GRANT ALL PRIVILEGES ON coffeepos.* TO 'coffeepos'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Make the app user's authn MySQL-native so PHP `PDO` can use a password:

```bash
sudo mysql
```

```sql
ALTER USER 'coffeepos'@'localhost' IDENTIFIED WITH mysql_native_password BY 'A-strong-app-password-here';
FLUSH PRIVILEGES;
EXIT;
```

---

## 5. First deploy (clone + configure + migrate)

### 5.1 Put the code in place

```bash
sudo mkdir -p /var/www/coffeepos
sudo chown ubuntu:ubuntu /var/www/coffeepos
cd /var/www/coffeepos
git clone https://github.com/my-github-name/coffeepos.git .
```

### 5.2 Create the production `.env`

Copy part of your local `.env` and switch to production values. The keys below
are the ones that matter:

```bash
cp .env.example .env
nano .env
```

| Key | Value for production |
|---|---|
| `APP_NAME` | `Coffee POS` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` ⚠️ (never `true` in production) |
| `APP_KEY` | empty — you'll run `php artisan key:generate` next (fills it) |
| `APP_URL` | `http://pos.example.com` or `http://10.0.0.123` |
| `DB_CONNECTION` | `mysql` |
| `DB_HOST` | `127.0.0.1` |
| `DB_PORT` | `3306` |
| `DB_DATABASE` | `coffeepos` |
| `DB_USERNAME` | `coffeepos` |
| `DB_PASSWORD` | the password from step 4 |
| `SESSION_DRIVER` | `database` (already default) |
| `CACHE_STORE` | `database` (already default) |
| `QUEUE_CONNECTION` | `database` (already default) |
| `MAIL_MAILER` | your real mailer, or keep `log` for now |
| `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` | your OAuth creds (set `”^(pos|your-domain)`... put the production domain in the Google console callback) |
| `STRIPE_KEY` / `STRIPE_SECRET` | live keys if you process real payments |

### 5.3 Install, generate key, migrate, link storage

```bash
cd /var/www/coffeepos
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
php artisan key:generate
php artisan migrate --force

# Storage symlink (used for any storage/app/public content)
php artisan storage:link

# Optional demo data — DO NOT run on a server that already has real data
# php artisan db:seed --class=DemoDataSeeder

# Compile all runtime caches now (mirrors what the workflow does)
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 5.4 Fix ownership for PHP-FPM

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
```

---

## 6. Nginx site configuration

Create `/etc/nginx/sites-available/coffeepos` (with your real `server_name`):

```nginx
server {
    listen 80;
    server_name pos.example.com 10.0.0.123;

    root /var/www/coffeepos/public;
    index index.php;

    charset utf-8;
    client_max_body_size 20M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; access_log off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable and reload:

```bash
sudo ln -s /etc/nginx/sites-available/coffeepos /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

Open `http://pos.example.com` (or `http://10.0.0.123`) in a browser. You should
see the app.

### 6.1 (Optional) HTTPS with Let's Encrypt

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d pos.example.com
```

Certbot rewrites the server block to listen on 443 and auto-renews. Update
`APP_URL` in `.env` to `https://pos.example.com`, then:

```bash
cd /var/www/coffeepos
php artisan config:cache   # re-read APP_URL
sudo systemctl reload php8.3-fpm
```

---

## 7. Laravel scheduler and (optional) queue worker

The app's `QUEUE_CONNECTION` and `CACHE_STORE` are `database`, so no Redis is
needed. Add the scheduler cron — Laravel 11 ships `schedule:run` which is
safe to run every minute even if you don't use scheduled jobs yet:

```bash
crontab -e
```

Add the line (change the path if needed):

```
* * * * * cd /var/www/coffeepos && php artisan schedule:run >> /dev/null 2>&1
```

If you later use queued jobs (Stripe webhooks, mails, etc.), run a worker. With
`systemd` you can create `/etc/systemd/system/queue-worker.service`:

```ini
[Unit]
Description=Laravel queue worker
After=network.target

[Service]
User=www-data
Group=www-data
WorkingDirectory=/var/www/coffeepos
ExecStart=/usr/bin/php /var/www/coffeepos/artisan queue:work --sleep=3 --tries=3
Restart=always

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now queue-worker
# After each deploy: sudo systemctl restart queue-worker
```

---

## 8. GitHub Actions: automate deploys on push to `main`

### 8.1 Add the workflow file

The workflow is already in the repo at:

```
.github/workflows/deploy.yml
```

It builds `public/build` with Vite, runs `composer install --no-dev`,
`rsync`s the code to `/var/www/coffeepos` (excluding `.env`, `storage`,
`vendor`, upload dirs, …), then on the server runs:

```
php artisan down → composer install --no-dev → migrate --force →
config/route/view cache rebuild → chown storage → php artisan up →
systemctl reload php8.3-fpm
```

### 8.2 Add the secrets

In GitHub ▸ your repo ▸ **Settings ▸ Secrets and variables ▸ Actions ▸ New
repository secret**, add:

| Secret | Value |
|---|---|
| `EC2_HOST` | your EC2 **public IPv4** (or domain), e.g. `10.0.0.123` |
| `EC2_USERNAME` | `ubuntu` |
| `EC2_SSH_KEY` | the **entire content** of `coffeepos-key.pem` (the private key) |
| `EC2_HEALTH_URL` | optional: `https://pos.example.com/up` to enable the health check step |

> The private key must belong to the key pair your instance was launched with.
> If you used an existing key pair, re-download its `.pem` from EC2 → Key pairs.

### 8.3 Push and watch it fly

```bash
git add .
git commit -m "chore: deploy pipeline"
git push origin main
```

Open the **Actions** tab → you'll see `Deploy to AWS EC2` run: Checkout →
composer → npm build → rsync → artisan steps → health check. When it's green
the site is already serving the latest `main`.

> First run tip: if the deploy is interrupted mid-way, just push again — the
> steps are idempotent (migrations, caches, ownership, reload).

---

## 9. The daily dev→live loop

1. Work on a feature branch locally (test locally — **never** test against the
   live DB).
2. Merge / push to `main`.
3. GitHub Actions deploys automatically. Your changes are live in ~1–2 minutes.
4. Watch the run; if anything fails, the site stays on the previous code
   (`php artisan down` → error → `php artisan up` without the failing step is
   avoided because the workflow stops before `php artisan up` — the site simply
   briefly shows maintenance while you fix the run).

### Rolling back

```bash
ssh -i coffeepos-key.pem ubuntu@10.0.0.123
cd /var/www/coffeepos
git log --oneline -10          # find the commit before the regression
git checkout <previous-commit>
# re-run the artisan steps by hand:
php artisan down --retry=60 || true
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
sudo chown -R www-data:www-data storage bootstrap/cache
php artisan up
sudo systemctl reload php8.3-fpm
```

---

## 10. Security checklist (recommended)

- [ ] Keep `APP_DEBUG=false` in production.
- [ ] Restrict security-group SSH source to your office IP if possible.
- [ ] Remove the default Nginx page (`sudo rm -f /etc/nginx/sites-enabled/default`).
- [ ] Use strong DB and app passwords; never commit `.env`.
- [ ] Rotate/limit Google OAuth + Stripe keys to the production domain.
- [ ] Take a DB backup now and on a schedule (see below).

### Database backup (quick + dirty)

```bash
# daily 3am backup, keep 14 days
mkdir -p ~/backups
crontab -l > /tmp/crontab.txt
echo '0 3 * * * mysqldump -u coffeepos -p"DBPASS" coffeepos | gzip > ~/backups/coffeepos-$(date +\%F).sql.gz && find ~/backups -name "*.sql.gz" -mtime +14 -delete' >> /tmp/crontab.txt
crontab /tmp/crontab.txt
```

---

## 11. Troubleshooting

| Symptom | Likely cause | Fix |
|---|---|---|
| 502 Bad Gateway from Nginx | PHP-FPM not running / wrong socket | `sudo systemctl status php8.3-fpm`; check socket path in the Nginx config matches `/run/php/php8.3-fpm.sock` |
| 500 error / blank page | stale or missing cache, permission | `sudo tail -50 /var/www/coffeepos/storage/logs/laravel.log`; re-run `php artisan config:cache`; `chown -R www-data:www-data storage bootstrap/cache` |
| 403 on CSS/images | wrong ownership/permissions | files must be readable by `www-data` |
| 419 Page Expired | session driver or APP_KEY different | session uses DB; ensure `php artisan migrate` ran and `APP_KEY` stable |
| Composer runs out of memory | small instance | add swap (step 3) or `composer install --no-scripts` |
| Uploads vanish after a deploy | upload dir rule missing | keep `productImages/`, `adminProfile/`, `customerProfile/` excluded from rsync `--delete` (already in the workflow) |
| Deploy fails at `ssh-keyscan` / rsync | wrong secret or no key pair | verify `EC2_SSH_KEY` is the exact private key of the instance; test `ssh -i coffeepos-key.pem ubuntu@<HOST>` from your machine |
| Actions can't reach the server | security group | port 22 must be open from `0.0.0.0/0` (or GitHub's runner IP ranges) for the workflow's SSH |

### Useful commands

```bash
tail -f /var/www/coffeepos/storage/logs/laravel.log   # live app errors
journalctl -u nginx -f                                 # nginx errors
journalctl -u php8.3-fpm -f                            # php-fpm errors
php artisan about                                      # app config sanity check
```

---

## 12. Files touched by this change

- `.github/workflows/deploy.yml` — the automation pipeline (this guide's step 8)
- `DEPLOY_AWS_EC2.md` — this document

No application code, routes, migrations or tests were modified.