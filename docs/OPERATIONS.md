# D STORE - Operational Runbook & Disaster Recovery

## 1. Routine Maintenance & Monitoring
- **Health Checks**: `/up` endpoint verifies web, database connection, and cache responsiveness.
- **Queue Health**: Monitor failed jobs using `php artisan queue:failed`. Retry transient network failures with `php artisan queue:retry all`.
- **Scheduled Jobs**:
  - `orders:expire-reservations` (Runs every minute): Reconciles expired unpaid checkouts and releases locked stock and coupons.
  - `loyalty:expire-lots` (Runs daily at midnight UTC): Marks expired point lots and adjusts active balance projections.

## 2. Backup & Restore Drill
- **Database Dump**:
  ```bash
  mysqldump -u root -p --single-transaction --quick --lock-tables=false dstore > backup_$(date +%Y%m%d).sql
  ```
- **Restore Rehearsal**:
  ```bash
  mysql -u root -p dstore_restore < backup_20261103.sql
  ```
- **Media Backups**: Synchronize `storage/app/public` with S3-compatible offsite object storage.

## 3. Safe Rollbacks & Migrations
- All migrations must have a working `down()` method where structurally possible.
- Non-destructive schema updates: add nullable columns or new tables before deploying code changes.
- In case of a critical release rollback:
  ```bash
  php artisan down --secret="dstore-ops-bypass"
  git checkout <previous-stable-tag>
  composer install --no-dev
  php artisan migrate
  php artisan up
  ```
