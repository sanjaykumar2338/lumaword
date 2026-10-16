# LumaWord

LumaWord is an AI-powered Christian discipleship and sermon-study web application. Phase 3 adds persistent, transcript-grounded Ask Luma conversations to authenticated sermon intake, queued processing, the sermon library, and structured study notes.

## Stack

- Laravel 13 / PHP 8.3+
- React 19, TypeScript, Tailwind CSS 4, and Vite
- MySQL 8+; Laravel Sanctum; database queues by default
- OpenAI Responses API and Audio Transcriptions API
- PHPUnit 12

## Requirements and setup

Install PHP 8.3–8.5, Composer 2, Node.js 24 LTS, npm, and MySQL 8+ or a compatible MariaDB release.

    git clone https://github.com/sanjaykumar2338/lumaword.git
    cd lumaword
    composer install
    cp .env.example .env
    php artisan key:generate
    npm ci

Create a MySQL database named lumaword, configure DB settings in .env, then run:

    php artisan migrate
    composer run dev

Open http://localhost:8000. Run the durable queue worker in another terminal:

    php artisan queue:work --queue=default --timeout=180 --tries=3

The worker timeout must remain lower than DB_QUEUE_RETRY_AFTER.

## OpenAI setup

Keep credentials server-side:

    OPENAI_API_KEY=
    OPENAI_MODEL=gpt-4o-mini
    OPENAI_TRANSCRIPTION_MODEL=gpt-4o-mini-transcribe

Analysis uses strict JSON-schema structured output and validates the returned structure again before persistence. Model, provider, prompt version, response ID, and usage metadata are retained. No successful AI result is faked when credentials are missing.

## Supported sermon inputs

- Public URL: verified public HTTP/HTTPS targets. Phase 2 does not scrape pages, follow redirects, bypass authentication, download protected media, or claim every YouTube link has a transcript. Add a pasted transcript when an authorized provider transcript is unavailable.
- Pasted transcript: 200–120,000 characters by default, editable before reprocessing.
- Authorized upload: flac, mp3, mp4, mpeg, mpga, m4a, ogg, wav, and webm, up to 25 MB by default. Random filenames are stored on Laravel's private local disk and deleted after successful transcription.

Configurable safeguards:

    SERMON_MIN_TRANSCRIPT_CHARACTERS=200
    SERMON_MAX_TRANSCRIPT_CHARACTERS=120000
    SERMON_MAX_UPLOAD_KB=25600
    SERMON_MAX_DURATION_SECONDS=7200
    SERMON_DAILY_PROCESSING_LIMIT=10
    SERMON_JOB_TIMEOUT=180
    SERMON_PROCESSING_LOCK_SECONDS=240
    SERMON_MAX_OUTPUT_TOKENS=6000
    SERMON_QA_MAX_QUESTION_CHARACTERS=2000
    SERMON_QA_DAILY_QUESTIONS=20
    SERMON_QA_RECENT_MESSAGES=8
    SERMON_QA_MAX_OUTPUT_TOKENS=1200
    SERMON_QA_MAX_CONTEXT_CHARACTERS=12000

These are safeguards, not final Free/Premium entitlements. The duration ceiling is enforced when the transcription provider reports duration; file size is always enforced. Production environments requiring mandatory preflight duration inspection still need an approved media-probe strategy.

## Processing lifecycle

    submitted / awaiting transcript
      -> queued
      -> transcribing (uploads only)
      -> transcript ready
      -> analyzing
      -> completed

Terminal retry exhaustion moves the sermon to failed. Unique jobs, a cache lock, atomic status changes, stable API idempotency keys, and update-or-create note persistence prevent repeated clicks from creating duplicate application work.

## Protected API

All endpoints use Sanctum and SermonPolicy ownership checks:

    POST   /api/v1/sermons
    GET    /api/v1/sermons
    GET    /api/v1/sermons/{sermon}
    PATCH  /api/v1/sermons/{sermon}
    DELETE /api/v1/sermons/{sermon}
    POST   /api/v1/sermons/{sermon}/transcript
    POST   /api/v1/sermons/{sermon}/process
    GET    /api/v1/sermons/{sermon}/status
    GET    /api/v1/sermons/{sermon}/notes
    GET    /api/v1/sermons/{sermon}/conversation
    GET    /api/v1/sermons/{sermon}/conversation/messages
    POST   /api/v1/sermons/{sermon}/conversation/messages

## Verification

    php artisan test --compact
    npm run typecheck
    npm run build
    vendor/bin/pint --dirty --format agent
    composer audit --locked
    npm audit --audit-level=high

Tests use isolated SQLite and fake every external OpenAI call. CI never requires an API key.

## Security and cost controls

- Never commit .env, credentials, API keys, user content, vendor, node_modules, or runtime storage.
- Use HTTPS, secure cookies, a durable queue, and a shared lock-capable cache in production.
- Media stays outside the public web root; transcripts and notes require owner authorization.
- URL checks block credentials, unusual ports, unresolved hosts, and private/reserved addresses.
- Transcript content is explicitly treated as untrusted data in the AI prompt.
- Configure provider spend limits and monitor actual token/audio usage. Cost depends on selected models and workload; no fixed estimate is invented.

See docs/ARCHITECTURE.md, docs/PHASE_3_SERMON_QA.md, docs/PHASE_STATUS.md, and docs/OPEN_DECISIONS.md.

## License

Proprietary project for LumaWord. No reuse license has been granted.
