# Phase 3 — Sermon-Specific Questions and Answers

## Implemented features

Authenticated sermon owners can open **Ask Luma** on a sermon detail page, use a suggested question or write their own, ask follow-up questions, inspect supporting transcript excerpts, retry a failed question, page through older messages, and return to the conversation later. The interface explains when Q&A is unavailable and includes an AI study-aid disclaimer.

Phase 3 deliberately uses one conversation per user per sermon. This keeps the MVP interface and ownership model simple while preserving the full message history.

## Architecture

- `AskSermonQuestion` coordinates eligibility, locking, idempotent persistence, recent history, provider failures, and retries.
- `SermonQuestionAnswerer` isolates the AI boundary; `OpenAiSermonQuestionAnswerer` uses the existing server-side OpenAI Responses API configuration.
- `SermonTranscriptContextSelector` splits long transcripts into bounded sections and ranks them with question terms. No vector database or external retrieval service is used.
- The browser generates a UUID for each question. Reusing it returns the stored answer, while a failed attempt can safely reuse it without creating duplicate messages.
- Calls are synchronous for this MVP. A per-conversation cache lock prevents overlapping answers, and the UI displays the actual waiting and failure states.

## Database tables

### `sermon_conversations`

Stores `user_id`, `sermon_id`, an optional title, and timestamps. A unique `(user_id, sermon_id)` constraint enforces the one-conversation decision. User and sermon deletes cascade.

### `sermon_messages`

Stores the conversation, optional replied-to user message, role, content, source metadata, processing status, client request UUID, OpenAI metadata, token usage, and timestamps. Unique constraints on `(conversation_id, client_request_id)` and `reply_to_message_id` prevent duplicate questions and answers. Replacing a sermon transcript clears its prior conversation because those answers no longer describe the current source.

## API endpoints

All endpoints require Sanctum authentication and sermon ownership.

| Method | Endpoint | Purpose |
| --- | --- | --- |
| `GET` | `/api/v1/sermons/{sermon}/conversation` | Conversation metadata and current availability |
| `GET` | `/api/v1/sermons/{sermon}/conversation/messages` | Paginated history; the newest page is returned in chronological display order |
| `POST` | `/api/v1/sermons/{sermon}/conversation/messages` | Validate, answer, and persist a question |

The POST body is `{ "question": "...", "client_request_id": "UUID" }`. It returns the conversation, user message, and assistant answer. Provider failures return a safe `502`; unavailable sermon states and overlapping requests return `409`; validation returns `422`; limits return `429`.

## Prompting and context strategy

The transcript is primary. Structured notes supply secondary context. Transcript sections are scored against meaningful terms in the current question, then limited by configured chunk and character budgets. A configurable number of recent completed messages supports follow-ups.

The developer prompt states that transcripts, notes, and conversation content are untrusted quoted material. It requires source-only answers, qualified interpretations, clear uncertainty, and no invented statements, Bible references, quotations, or timestamps. The model returns only source indices; the server converts valid indices into excerpts from the selected transcript text and discards unknown indices. Because current transcripts have no reliable timecode structure, Phase 3 does not display timestamps.

Low lexical retrieval confidence is disclosed to the model, which is instructed to acknowledge when the source does not establish an answer.

## Configuration

```dotenv
SERMON_QA_MAX_QUESTION_CHARACTERS=2000
SERMON_QA_DAILY_QUESTIONS=20
SERMON_QA_RECENT_MESSAGES=8
SERMON_QA_MAX_OUTPUT_TOKENS=1200
SERMON_QA_MAX_CONTEXT_CHARACTERS=12000
SERMON_QA_MAX_CONTEXT_CHUNKS=6
SERMON_QA_CONTEXT_CHUNK_CHARACTERS=1800
SERMON_QA_SOURCE_EXCERPT_CHARACTERS=500
SERMON_QA_MESSAGES_PER_PAGE=20
SERMON_QA_TIMEOUT=45
SERMON_QA_LOCK_SECONDS=60
```

These values are abuse and cost safeguards, not subscription entitlements. The cache store used in production must support atomic locks, and the lock lifetime must remain longer than the OpenAI timeout.

## Security and reliability

- Sermon policy checks prevent cross-user sermon and conversation access.
- Form Request validation trims questions, rejects empty or oversized text, and requires UUID request identifiers.
- The daily user limiter caps expensive requests.
- OpenAI credentials and calls remain server-side; raw provider errors are reported internally but never returned to clients.
- Bounded transcript and recent-message context prevents unlimited prompt growth.
- React renders all question, answer, and excerpt content as escaped text.
- Database uniqueness plus the conversation lock prevents duplicate submissions and assistant replies.

## Testing

Automated coverage includes authentication, ownership, empty history, persistence, mocked success, safe provider failure, retry, duplicate requests, input validation, missing transcripts, processing and failed states, daily rate limiting, follow-up context, transcript selection, server-verified source excerpts, and transcript prompt-injection boundaries.

Run:

```bash
vendor/bin/phpunit
vendor/bin/pint --dirty --format agent
npm run typecheck
npm run build
```

## Known limitations and deployment requirements

- No automatic YouTube or Vimeo transcript retrieval is included.
- Retrieval is lexical rather than semantic; indirect wording can produce a low-confidence answer.
- Transcript timestamps are not stored, so sources use section labels and excerpts.
- AI generation remains synchronous. If production latency becomes unacceptable, a later approved change can move the same idempotent message workflow to queued jobs and polling.
- A real OpenAI end-to-end result is not verified by mocked tests. Production needs `OPENAI_API_KEY`, the configured model, outbound HTTPS, provider spend limits, and database migrations.
- This phase does not add subscriptions, devotionals, apologetics, admin tools, quote extraction, or social-media generation.
