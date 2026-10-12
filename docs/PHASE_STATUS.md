# Phase Status

Updated: 2026-10-12

## Phase 1 — Complete

Landing page, React foundation, authentication/password reset, protected dashboard/settings, Sanctum API structure, MySQL-ready migrations, tests, documentation, and CI remain operational.

## Phase 2 — Complete in feature branch

- Supplied LUMA logo integrated; favicon and Apple touch assets generated
- User-owned sermons and structured notes with factories, indexes, relationships, and policy
- Safe URL intake with transcript fallback and no restricted scraping
- Editable transcript and allowlisted private media upload intake
- Queued transcription and strict-schema OpenAI sermon analysis
- Status lifecycle, retries, safe failure state, reprocessing, locks, and idempotency
- Configurable transcript, upload, duration, output, request, and daily safeguards
- Responsive submission, library, detail, transcript, status, and notes interfaces
- Protected versioned API with pagination and rate limiting
- Automated external-service fakes and Phase 1 regression coverage
- Queue, storage, AI, limits, testing, and cost documentation

A real AI end-to-end run is not verified without a user-supplied OPENAI_API_KEY. No fake success is reported.

## Later phases — Not started

| Phase | Scope | Status |
| --- | --- | --- |
| 3 | Sermon-scoped AI questions | Not started |
| 4 | Devotionals, reading plans, weekly journey | Not started |
| 5 | Apologetics and theological claim analysis | Not started |
| 6 | Free/paid subscriptions and Stripe | Not started |
| 7 | Admin tools, notifications, monitoring | Not started |
| 8 | Launch hardening and full release testing | Not started |

Phase 2 does not include sermon chat, devotional generation, apologetics verdicts, subscriptions, or admin tooling.
