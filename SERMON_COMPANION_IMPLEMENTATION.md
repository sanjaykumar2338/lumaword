# Sermon Companion Backend

## Implemented

Sermon Companion is an additive authenticated API under `/api/v1/sermon-companions`. It supports written notes, optional speaker/church/date/reference metadata, private image/audio attachments, reviewable extracted text fields, five- or seven-day devotional plans, devotional completion progress, private journal autosave, and Scripture memory practice persistence.

Attachments are stored on the non-public `local` disk and dispatched to `ProcessSermonCompanionAttachment`. The processor uses the configured OpenAI Responses vision model for images and the existing OpenAI transcription configuration for audio. Extraction never fills unreadable text silently; failures are persisted as safe user-facing processing errors.

Sunday in One Page recap and devotional generation run through unique queued jobs. OpenAI receives only bounded, tagged source material. The server validates structured JSON, verifies all Scripture references through `BibleReaderService`, and inserts Bible text from the configured public-domain translation; model output can never replace verified Scripture text. Memory cards use OpenAI-selected blank words, while the verse text and answer words are checked against the verified passage before persistence.

## API endpoints

- `GET|POST /api/v1/sermon-companions`
- `GET|PATCH|DELETE /api/v1/sermon-companions/{sermon_companion}`
- `POST /api/v1/sermon-companions/{sermon_companion}/recap` — returns `202` and queues Sunday in One Page generation; accepts `Idempotency-Key`.
- `POST /api/v1/sermon-companions/{sermon_companion}/attachments` (`kind=image|audio`, multipart `file`)
- `POST /api/v1/sermon-companions/{sermon_companion}/devotional-plans` (`days_count=5|7`) — returns `202` while the AI job runs; accepts `Idempotency-Key`.
- `GET /api/v1/sermon-companion-plans/{plan}/days`
- `POST /api/v1/sermon-companion-days/{day}/complete`
- `GET|POST /api/v1/sermon-companions/{sermon_companion}/journal`
- `DELETE /api/v1/sermon-companion-journal/{entry}`
- `GET /api/v1/sermon-companions/{sermon_companion}/memory-cards`
- `POST /api/v1/sermon-companion-memory-cards/{memory_card}/practice`

Companion detail responses expose `status` values including `awaiting_review`, `extracting_notes`, `generating_recap`, `completed`, and `failed`. Plan responses expose `status` values `queued`, `generating`, `completed`, and `failed`. Flutter should poll the detail or plan endpoint after a `202` response and display `processing_error` when status is `failed`.

Example recap request:

```http
POST /api/v1/sermon-companions/42/recap
Idempotency-Key: recap-42-v1
Authorization: Bearer <token>
```

Example queued response:

```json
{"data":{"id":42,"status":"generating_recap","recap":null},"message":"Sunday in One Page recap queued for generation."}
```

Example devotional day fields are `day_number`, `title`, `theme`, `scripture_reference`, `translation`, `scripture_text`, `reflection`, `reflection_question`, `practical_application`, `prayer`, and `completed_at`.

Journal autosave accepts `entry_type` (`reflection`, `journal`, `prayer`, `application`, or `midweek_check_in`), `content`, optional `devotional_day_id`, and a stable `client_key`. Reusing the same owner, companion, day, type, and client key updates the existing row.

Journal writes accept `client_key` and use a database uniqueness constraint for autosave idempotency. All records enforce authenticated ownership in the request/controller boundary.

## Database changes

Ten additive tables/migrations are now used: companions, attachments, devotional plans, devotional days, progress, journal entries, memory cards, memory practice, reminder deliveries, and notification preference fields. Foreign keys cascade from the companion and user records; private attachment paths are hidden from resources. Reminder deliveries have a unique plan/kind/date key to prevent duplicates under concurrent scheduler runs.

## Configuration

Existing `OPENAI_API_KEY`, `OPENAI_BASE_URL`, `OPENAI_MODEL`, `OPENAI_TIMEOUT`, and `OPENAI_TRANSCRIPTION_MODEL` are reused. Optional `OPENAI_VISION_MODEL` selects the vision-capable model. `SERMON_COMPANION_MAX_UPLOAD_KB` defaults to `25600`; `SERMON_COMPANION_MAX_OUTPUT_TOKENS` defaults to `5000`. Companion AI usage is controlled by the `sermon_companion` entitlement feature. Run a queue worker in production, for example `php artisan queue:work --tries=3`.

Mobile notification preferences now include `sermon_companion_reminders` and `sermon_companion_midweek_check_ins`, alongside the existing `reminder_time` and `timezone`. The scheduler command is `app:dispatch-sermon-companion-reminders`, registered every minute with overlap protection. It queues daily reminders for the current incomplete day and one midweek check-in after at least two completed days. Delivery uses the existing Firebase/native and Web Push gateways; no delivery is claimed unless credentials are configured.

Provider credentials, FCM/APNs delivery, and live OpenAI responses still require provider-environment testing. No YouTube audio downloading or transcript scraping was added.

Live OpenAI, Firebase/APNs, and Web Push credentials still require provider-environment testing. No YouTube audio downloading or transcript scraping was added.

## Verification

- Companion API, AI, progress, and reminder regression tests cover mocked OpenAI responses, verified Bible text, five- and seven-day plans, memory cards, idempotent journal writes, completion progress, and reminder deduplication.
- Full PHPUnit suite: 198 tests passed, 820 assertions; Pint passed; PHP syntax, route registration, migration rollback/replay, and `git diff --check` passed.
- Laravel Pint and PHP syntax checks passed.

The Flutter project was not modified. Flutter should call the resource endpoints above, poll attachment/companion status via the companion detail endpoint, send a stable `client_key` for autosave entries, and treat `202` as queued rather than completed.
