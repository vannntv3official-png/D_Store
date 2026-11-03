# D STORE - Deployment Guide

## 1. Environment Requirements
- Linux or Windows Server with PHP 8.3+
- Extensions: `bcmath, curl, fileinfo, gd, intl, mbstring, openssl, pdo_mysql, zip`
- MySQL 8.0+ or MariaDB 10.11+
- Node.js 20+ and NPM for asset compilation
- Web Server: Nginx or Caddy with PHP-FPM

## 2. Docker Compose Setup
For reproducible local and staging environments, a `docker-compose.yml` is provided:
```bash
docker compose up -d
docker compose exec app php artisan migrate --seed
docker compose exec app npm run build
```
Services included:
- `app`: PHP 8.3-FPM
- `web`: Nginx
- `db`: MySQL 8.4
- `mailpit`: Local SMTP and web UI (port 8025)
- `queue`: Worker container running `php artisan queue:work`

## 3. Production Deployment Checklist
1. Clone repository and install dependencies:
   ```bash
   composer install --no-dev --optimize-autoloader
   npm ci && npm run build
   ```
2. Configure `.env` with production database, app key, and secure cookie domains.
3. Run migrations safely:
   ```bash
   php artisan migrate --force
   ```
4. Optimize framework caches:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
5. Configure supervisor daemon for background queues and cron schedule runner:
   ```bash
   * * * * * cd /var/www/dstore && php artisan schedule:run >> /dev/null 2>&1
   ```
