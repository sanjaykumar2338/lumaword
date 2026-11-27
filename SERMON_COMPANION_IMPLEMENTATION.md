# Sermon Companion Backend

## Implemented

Sermon Companion is an additive authenticated API under `/api/v1/sermon-companions`. It supports written notes, optional speaker/church/date/reference metadata, private image/audio attachments, reviewable extracted text fields, five- or seven-day devotional plans, devotional completion progress, private journal autosave, and Scripture memory practice persistence.

Attachments are stored on the non-public `local` disk and dispatched to `ProcessSermonCompanionAttachment`. The processor uses the configured OpenAI Responses vision model for images and the existing OpenAI transcription configuration for audio. Extraction never fills unreadable text silently; failures are persisted as safe user-facing processing errors. Devotional content currently uses a verified Bible passage plus clearly marked starter reflection/application/prayer content; full LUMA-generated recap and devotional prose remains a follow-up integration point.

## API endpoints

- `GET|POST /api/v1/sermon-companions`
- `GET|PATCH|DELETE /api/v1/sermon-companions/{sermon_companion}`
- `POST /api/v1/sermon-companions/{sermon_companion}/attachments` (`kind=image|audio`, multipart `file`)
- `POST /api/v1/sermon-companions/{sermon_companion}/devotional-plans` (`days_count=5|7`)
- `GET /api/v1/sermon-companion-plans/{plan}/days`
- `POST /api/v1/sermon-companion-days/{day}/complete`
- `GET|POST /api/v1/sermon-companions/{sermon_companion}/journal`
- `DELETE /api/v1/sermon-companion-journal/{entry}`
- `GET /api/v1/sermon-companions/{sermon_companion}/memory-cards`
- `POST /api/v1/sermon-companion-memory-cards/{memory_card}/practice`

Journal writes accept `client_key` and use a database uniqueness constraint for autosave idempotency. All records enforce authenticated ownership in the request/controller boundary.

## Database changes

Eight additive tables were introduced: companions, attachments, devotional plans, devotional days, progress, journal entries, memory cards, and memory practice. Foreign keys cascade from the companion and user records; private attachment paths are hidden from resources.

## Configuration

Existing `OPENAI_API_KEY`, `OPENAI_BASE_URL`, `OPENAI_TIMEOUT`, and `OPENAI_TRANSCRIPTION_MODEL` are reused. Optional `OPENAI_VISION_MODEL` selects the vision-capable model. `SERMON_COMPANION_MAX_UPLOAD_KB` defaults to `25600`. Run a queue worker in production, for example `php artisan queue:work --tries=3`.

Provider credentials, FCM/APNs delivery, and live OpenAI responses still require provider-environment testing. No YouTube audio downloading or transcript scraping was added.

Notification preference fields and reminder scheduling for Sermon Companion, recap AI generation, and provider-backed devotional prose remain production blockers for the complete product brief.

## Verification

- Companion feature tests: 3 passing, 14 assertions.
- Existing sermon and sermon-devotional tests: 21 passing, 84 assertions.
- Migrations applied successfully to the configured local SQLite database.
- Laravel Pint and PHP syntax checks passed.

The Flutter project was not modified. Flutter should call the resource endpoints above, poll attachment/companion status via the companion detail endpoint, and send a stable `client_key` for autosave entries.
