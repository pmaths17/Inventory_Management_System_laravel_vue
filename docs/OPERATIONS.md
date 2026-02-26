# Operations Runbook

## Production Security Checklist
- Set `APP_ENV=production` and `APP_DEBUG=false`.
- Set strict session cookie settings in `.env`:
  - `SESSION_SECURE_COOKIE=true`
  - `SESSION_SAME_SITE=lax` (or `strict` if frontend allows)
- Set `SANCTUM_STATEFUL_DOMAINS` to only trusted frontend domains.
- Set `SESSION_DOMAIN` to your production domain.
- Configure trusted proxies and HTTPS termination correctly.

## RBAC and Access Control
- Keep at least one active admin user at all times.
- Do not archive/delete system roles (`admin`, `staff`, `viewer`) unless migrating with a rollback plan.
- Review audit logs periodically for:
  - `user.updated`, `user.deleted`
  - `role.updated`, `role.deleted`
  - `permission.updated`, `permission.deleted`

## Backup and Restore
### Backup
- MySQL example:
  - `mysqldump -u <user> -p <database> > backup_YYYYMMDD.sql`
- SQLite example:
  - `copy database\\database.sqlite backup_YYYYMMDD.sqlite`

### Restore
- MySQL example:
  - `mysql -u <user> -p <database> < backup_YYYYMMDD.sql`
- SQLite example:
  - Replace `database/database.sqlite` with backup copy.

### Post-restore verification
- Run `php artisan migrate:status`.
- Run smoke checks:
  - Login works.
  - `/api/user` returns active roles only.
  - Critical pages load (`/dashboard`, `/users`, `/roles-permissions`).

## Observability
- Monitor and alert on:
  - Repeated `429` login responses.
  - Repeated `403` permission denials.
  - Unexpected spikes in failed auth attempts.

## Deployment Validation
- Run before deployment:
  - `php artisan test`
  - `npm run build`
- Run after deployment:
  - `php artisan config:cache`
  - `php artisan route:cache`
  - `php artisan view:cache`

