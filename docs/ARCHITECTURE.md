# LumaWord Architecture

## Phase 2 system

LumaWord remains a Laravel application serving a React single-page app and a versioned JSON API. Laravel is the system of record for the future native client.

    Browser / future native client
        -> Sanctum-authenticated /api/v1
        -> Form Requests + SermonPolicy + API Resources
        -> sermons and sermon_notes in MySQL
        -> queued ProcessSermon job
             -> private media transcription boundary
             -> transcript validation
             -> OpenAI strict-schema analysis boundary
             -> atomic notes/status persistence

## Backend boundaries

- SermonController owns resource listing, creation, reading, renaming, and deletion.
- Dedicated invokable controllers own transcript replacement, processing requests, status, and notes.
- SermonPolicy enforces per-user ownership.
- SermonProcessingDispatcher performs daily-limit checks, row locking, status transitions, and one after-commit dispatch.
- ProcessSermon is unique per sermon and also uses a cache lock. It is retryable, idempotent at persistence, and stores only safe failure text.
- SermonAnalyzer and MediaTranscriber contracts isolate external providers. OpenAiSermonService is the current server-side implementation.
- SermonNotesSchema supplies the strict JSON schema and performs defensive validation before persistence.
- SermonUrlService normalizes public HTTP/HTTPS URLs and rejects credentials, unusual ports, unresolved DNS, and private/reserved addresses. Phase 2 performs no page scraping or media downloads.

## Data and storage

The sermons table owns source metadata, status, private transcript text, media metadata, processing attempts, and timestamps. The sermon_notes table has one row per sermon and stores structured JSON plus provider/model/prompt metadata.

Uploads use the local private disk rooted at storage/app/private. Laravel generates random filenames. The source file is removed after successful transcription; deletion also removes any remaining file. Transcripts and notes are returned only through owner-authorized routes.

## Processing and frontend

URL-only records remain awaiting_transcript until an authorized transcript is supplied. Submitted transcripts/uploads are queued. Uploads move through transcribing; ready transcripts move through analyzing; successful work becomes completed. Final retry exhaustion becomes failed.

The browser never waits for transcription or analysis. DashboardPage provides intake and recent status. NewSermonPage provides three intake tabs. SermonLibraryPage adds search/filter/pagination. SermonDetailPage polls active work, edits transcripts, retries failures, and renders structured notes.

Brand uses the supplied LUMA artwork through a transparent derived mark; public/logo.png is preserved.

## Security and cost controls

Authentication, policy authorization, CSRF, upload content validation, private storage, SSRF checks, throttles, daily limits, transcript/media limits, HTTP timeouts, unique jobs, locks, prompt-injection instructions, and schema validation form the Phase 2 boundary.

Logs do not include transcript content. Provider credentials remain server-side. Production should use a shared lock-capable cache, durable queue, HTTPS, monitoring, and provider spend limits.
