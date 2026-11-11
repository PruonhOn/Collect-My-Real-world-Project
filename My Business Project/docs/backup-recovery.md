# Backup and Recovery

## 1. What must be protected

| Asset | Where | Criticality |
| --- | --- | --- |
| MySQL database | `my_business` | **Critical** — every business record |
| Application code | git repository | High |
| `.env` (secrets, keys) | server, secret manager | **Critical** — without `APP_KEY` the data is unreadable |
| Uploaded files | `storage/app` (logos, invoice PDFs) | High |
| Generated artefacts | `storage/framework/{cache,sessions,views}` | Low — regenerable |

`APP_KEY` is part of the backup set. A database dump restored with a different
`APP_KEY` cannot decrypt the stored values.

## 2. Objectives

| Metric | Target |
| --- | --- |
| RPO (maximum data loss) | ≤ 1 hour |
| RTO (maximum downtime) | ≤ 4 hours |
| Backup window | Hourly dumps, daily full snapshot, 30 day retention |
| Off-site copy | Daily, encrypted, different failure domain |

## 3. Backup strategy

### Automated dumps

`/usr/local/bin/my-business-backup.sh`:

```bash
#!/usr/bin/env bash
set -euo pipefail

BACKUP_DIR=/var/backups/my-business
STAMP=$(date +%Y%m%d-%H%M%S)
mkdir -p "$BACKUP_DIR"

mysqldump \
  --single-transaction --quick --routines --triggers --events \
  --default-character-set=utf8mb4 \
  my_business | gzip -9 > "$BACKUP_DIR/my_business-$STAMP.sql.gz"

tar -czf "$BACKUP_DIR/storage-$STAMP.tar.gz" -C /var/www/my-business/backend storage/app

find "$BACKUP_DIR" -type f -mtime +30 -delete
```

Cron entry (hourly, off the minute to avoid the top of the hour):

```
17 * * * * /usr/local/bin/my-business-backup.sh >> /var/log/my-business-backup.log 2>&1
```

`--single-transaction` keeps the dump consistent without locking the tables.

### MySQL binary logs

Enable binlogs so that a point-in-time restore is possible:

```ini
[mysqld]
log_bin = /var/log/mysql/mysql-bin.log
binlog_format = ROW
expire_logs_days = 14
```

### Encrypted off-site copy

```
41 3 * * * gpg --batch --yes -c /etc/my-business/backup.key \
      -o /var/backups/my-business/latest.sql.gz.gpg \
      /var/backups/my-business/my_business-$(date +\%Y\%m\%d).sql.gz \
      && aws s3 cp /var/backups/my-business/latest.sql.gz.gpg \
         s3://my-business-backups/ --storage-class STANDARD_IA
```

The encryption key lives outside the machine, in the secret manager.

### Verification

A backup that was never restored is not a backup. The restore drill below runs
monthly; the script must be part of the on-call runbook.

## 4. Restore procedure

### Full restore

```bash
# 1. Stop the application so nothing writes during the restore
sudo systemctl stop my-business-worker my-business-scheduler
sudo systemctl stop php8.4-fpm        # or nginx

# 2. Create the database
mysql -e "CREATE DATABASE my_business CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER 'my_business'@'localhost' IDENTIFIED BY '••••••••';"
mysql -e "GRANT ALL PRIVILEGES ON my_business.* TO 'my_business'@'localhost';"

# 3. Restore the dump
gunzip -c /var/backups/my-business/my_business-20261022-0300.sql.gz \
  | mysql my_business

# 4. Restore uploaded files
tar -xzf /var/backups/my-business/storage-20261022-0300.tar.gz \
  -C /var/www/my-business/backend

# 5. Restore the environment (from the secret manager)
cp /secure/my-business.env /var/www/my-business/backend/.env

# 6. Migrate to the current schema
cd /var/www/my-business/backend
php artisan migrate --force

# 7. Clear caches, restart services
php artisan optimize:clear
sudo systemctl start php8.4-fpm my-business-worker my-business-scheduler

# 8. Verify
curl -s https://api.example.com/api/v1/plans
php artisan migrate:status
```

### Point-in-time restore

```bash
# 1. Restore the last full backup
gunzip -c /var/backups/my-business/my_business-20261022-0300.sql.gz | mysql my_business

# 2. Replay the binary logs up to the desired moment
mysqlbinlog --start-datetime="2026-10-22 03:00:00" \
            --stop-datetime="2026-10-22 15:42:00" \
            /var/log/mysql/mysql-bin.000042 | mysql my_business
```

### Partial recovery (single tenant)

```bash
# Export one tenant
mysqldump --single-transaction my_business \
  --where="business_id=42" businesses > /tmp/business-42.sql

# ... import into the target environment after creating the parent rows
mysql my_business < /tmp/business-42.sql
```

## 5. Disaster scenarios

| Scenario | Action | Expected downtime |
| --- | --- | --- |
| Accidental `DELETE` on a tenant table | Restore from the hourly dump, then replay the binlog to the minute before the mistake | 15 min |
| Whole database corruption | Full restore from the last off-site copy + binlog replay | 2–4 h |
| Lost server | Provision a new host, restore the dump and `.env`, redeploy | 2–4 h |
| Bad migration | `php artisan migrate:rollback --step=1`, restore the affected rows | 30 min |
| Secret leak | Rotate the secret, invalidate the sessions, redeploy | 0 |
| Ransomware | Isolate the host, restore from the immutable off-site copy, rotate every secret | 4 h |

## 6. Post-restore checklist

- [ ] `php artisan migrate:status` shows no pending migration
- [ ] `GET /api/v1/plans` answers `200`
- [ ] A test business can sign in, create an order and generate an invoice
- [ ] The entitlement cache is warm: `php artisan cache:clear` before traffic
- [ ] The queue worker is running and the `failed_jobs` table is empty
- [ ] `payment_webhook_events` shows no stuck `received` rows
- [ ] The scheduler ran: `php artisan schedule:list` and the last run log
- [ ] Monitoring and alerting are green again
- [ ] The incident is documented with the timeline

## 7. Routine maintenance

| Task | Frequency |
| --- | --- |
| Verify the newest dump restores | Monthly |
| Copy the newest dump off-site | Daily |
| Check the binlog and dump growth | Weekly |
| Prune expired backups and binlogs | Automatic (30 days / 14 days) |
| Restore drill with a realistic dataset | Quarterly |
| Review the RPO/RTO targets with the team | Quarterly |
