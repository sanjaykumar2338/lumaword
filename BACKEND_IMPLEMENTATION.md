# LUMA backend implementation

## Required migrations

Run `php artisan migrate` before using the updated API. The new migrations add:

- notification reminder switches, local reminder time, and IANA timezone;
- `users.manual_premium_until` for separately identifiable manual grants;
- structured chapter assignments for reading-plan days.

Existing reading-plan enrollment and completion rows are not deleted or reset. Re-seed the catalog with `php artisan db:seed --class=ReadingPlanSeeder --no-interaction` to populate the complete-canon 30-day plan.

## Environment configuration

The following integrations remain disabled unless their complete credentials are supplied:

- YouTube OAuth: `YOUTUBE_CLIENT_ID`, `YOUTUBE_CLIENT_SECRET`, `YOUTUBE_REDIRECT_URI`.
- OpenAI generation/transcription: `OPENAI_API_KEY` and the existing model/timeout settings.
- Apple verification: `APPLE_PURCHASES_ENABLED`, bundle ID, key ID, issuer ID, private key, root certificate, and both configured product IDs.
- Google Play verification: `GOOGLE_PURCHASES_ENABLED`, package name, access token, and both configured product IDs.
- FCM native notifications: `FIREBASE_NOTIFICATIONS_ENABLED`, project ID, and service-account JSON.
- APNs native notifications: no APNs gateway is currently configured in this repository; Apple team ID, key ID, private key, and bundle ID plus an APNs provider implementation are still required for iOS delivery.
- Browser devotional reminders: the existing `VAPID_*` settings.

Run YouTube retrieval workers on the dedicated queue, for example `php artisan queue:work --queue=youtube,default`. The database queue's `retry_after` must remain longer than the configured sermon job timeout.

## API additions and changes

`POST /api/v1/sermons/{id}/devotional` requires an authenticated sermon owner, a completed sermon transcript, and at least one usable Scripture reference in the completed sermon notes. It returns the normal devotional resource with `201` and `meta.reused=false`; a repeat request returns the saved history item with `200` and `meta.reused=true`.

`GET|PUT /api/v1/mobile/notification-preferences` continues to accept the existing fields and additionally accepts `reading_plan_reminders`, `reminder_time` (`HH:MM`), and `timezone` (IANA timezone). Existing clients may omit the new fields; database defaults preserve their behavior.

Reading-plan days now expose `complete_canon` on the plan and a `readings` array on each day. The 30-day plan assigns every chapter in the bundled WEB canon exactly once. Existing `completed_day_ids` and enrollment endpoints remain unchanged.

Entitlement responses now identify `source` (`stripe`, `mobile_purchase`, `manual`, or null) and `canceled` while preserving paid access through a future cancellation/expiration date. Store purchases continue to fail closed when product IDs or provider credentials are missing.

## Production deployment runbook

The following is a command sequence for a standard Linux host. Replace the application path, database host, database name, and service names with production values. Do not run it until the release is approved.

```bash
cd /var/www/lumaword

# Backup first; keep the output outside the web root.
mysqldump --single-transaction --routines --triggers --hex-blob \
  -h DB_HOST -u DB_USER -p DB_NAME > /var/backups/lumaword-$(date +%Y%m%d-%H%M%S).sql

php artisan down --render="errors::503" --retry=60
git fetch origin main
git checkout main
git pull --ff-only origin main
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan migrate --force --no-interaction
php artisan db:seed --class=Database\\Seeders\\ReadingPlanSeeder --force --no-interaction
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart lumaword-default lumaword-youtube
php artisan migrate:status
php artisan queue:failed
curl --fail --silent --show-error https://lumaword.app/up
php artisan up
```

Run the default and YouTube workers as separate supervised processes. The YouTube process must include the dedicated queue and a timeout below the database queue `retry_after` value:

```bash
/usr/bin/php /var/www/lumaword/artisan queue:work database --queue=youtube,default --sleep=3 --tries=5 --timeout=180 --max-time=3600
```

For rollback, place the application in maintenance mode, use `git revert <release-commit>` (never force-push shared history), reinstall the matching Composer lockfile, clear/rebuild caches, restart both workers, and restore the database backup only when a schema/data rollback is required:

```bash
cd /var/www/lumaword
php artisan down --render="errors::503" --retry=60
git revert --no-edit <release-commit>
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
sudo supervisorctl restart lumaword-default lumaword-youtube
php artisan up
```

Do not run `migrate:rollback` blindly in production. Take a fresh backup, review migration dependencies, and restore the known-good backup if data/schema restoration is necessary.

## Operational verification still required

This repository does not verify live YouTube OAuth/caption access, running queue workers, Apple/Google store responses, or Firebase/APNs delivery. Those require provider credentials and production-like services; no successful external integration is claimed here.
