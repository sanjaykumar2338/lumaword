# LumaWord Architecture

## Phase 1 overview

LumaWord is a Laravel application that serves a React single-page web interface and a versioned JSON API. The backend remains the system of record so a future native iOS client can reuse authentication, user, and study APIs.

```text
Browser (React + TypeScript + Tailwind)
        │ same-origin HTTPS + Sanctum CSRF/session cookies
        ▼
Laravel web shell ─────────────── Laravel /api/v1
        │                               │
        │                               ├── auth controllers + Form Requests
        │                               ├── UserResource response contract
        │                               └── auth:sanctum protected endpoints
        │
        └────────────────────────────── MySQL
                                        ├── users / password reset tokens / sessions
                                        ├── Sanctum personal access tokens
                                        ├── cache
                                        └── jobs / failed jobs / job batches
```

## Frontend

- `resources/js/app.tsx` owns client routing.
- `resources/js/pages` contains route-level screens.
- `resources/js/components` contains reusable layout and form elements.
- `resources/js/context/AuthContext.tsx` owns current-user session state.
- `resources/js/lib/api.ts` centralizes JSON requests and Sanctum CSRF handling.
- `resources/css/app.css` contains Tailwind 4 theme tokens and minimal base styles.

Laravel protects `/dashboard` and `/settings` on the server. React repeats the route guard to handle client-side navigation and expired sessions cleanly.

## Backend

- API routes live in `routes/api.php` under `/api/v1`.
- Web shell and password-reset link routes live in `routes/web.php`.
- Controllers are grouped under `App\Http\Controllers\Api\V1`.
- Form Requests own validation and authorization boundaries.
- `App\Http\Resources\V1\UserResource` stabilizes user JSON and prevents accidental attribute exposure.
- Named rate limiters protect registration, login, reset-link, and password-reset endpoints.

Sanctum uses stateful cookie authentication for the first-party web SPA. `auth:sanctum` can also accept personal access tokens, but issuing and rotating native-app tokens is intentionally deferred until the mobile security contract is approved.

## Data

Phase 1 persists identity and framework infrastructure only. It does not create sermon, transcript, note, devotional, apologetics, subscription, or upload tables before those domains are defined.

MySQL is the runtime database. The test suite uses isolated in-memory SQLite for fast deterministic checks. Database-backed sessions, cache, and queues provide durable defaults; Redis can replace cache/queue drivers when workload and operations justify it.

## Security boundaries

- Passwords use Laravel's `hashed` model cast.
- State-changing browser requests use Sanctum CSRF cookies.
- Authentication responses expose only `UserResource` fields.
- Forgot-password responses do not reveal whether an email exists.
- Protected web and API routes enforce authentication independently.
- Secrets and local data are ignored by Git.
- Future user sermon content must use private storage and explicit authorization.

## Future extension points

- Phase 2 adds a user-owned sermon ingestion domain and queued processing.
- Phase 3 adds sermon-scoped retrieval and question answering.
- Phase 4 adds devotional and reading-plan models.
- Phase 5 adds claim-analysis sources and theological review controls.
- Phase 6 adds Stripe billing and subscription entitlements.
- Phase 7 adds Filament administration, notifications, and monitoring.

These are boundaries, not implemented features.
