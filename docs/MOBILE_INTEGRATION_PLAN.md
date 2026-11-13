# LUMA mobile integration plan

## Foundation delivered

- Flutter app shell with LUMA colors and the supplied `public/logo-mark.png`.
- Splash, welcome/onboarding, login, registration, forgot-password request,
  persistent bearer-token login, logout, dashboard, bottom navigation, profile,
  loading, and basic empty/feature-preview states.
- Secure token storage and one centralized API client/error type.
- Mobile-only Sanctum token endpoints; existing web session auth is unchanged.

## Reused API surface

The existing `/api/v1` API already covers user/profile/password, entitlements
and usage, billing status, promotional banners, sermons (including transcript,
media, processing, notes, and Ask LUMA), devotionals, reading plans and
enrollments, Bible translations/books/search/passages, Bible studies, daily
activity, beta feedback, and push-subscription persistence.

The native client should call these endpoints with the stored bearer token.
Laravel remains the authority for ownership, quotas, AI usage, and premium
entitlements.

## Missing or mobile-specific work

- Added `POST /api/v1/mobile/auth/login` and `POST /api/v1/mobile/auth/register`
  returning a personal-access token plus the user resource.
- Added `POST /api/v1/mobile/auth/logout` to revoke the current token.
- Native push provider registration is still needed. The existing
  `/push-subscriptions` contract stores browser Web Push keys; APNs/FCM device
  tokens need a separate versioned contract and server gateway before native
  notifications are enabled.
- Native subscription purchases require an approved Apple/Google billing design
  and server-side receipt validation. No billing mutation was added here.

## Next development task

Build the authenticated data layer and first vertical slice: sermon library →
sermon submission → processing status → generated notes. Add API fixtures,
pagination, retry/offline behavior, and a dedicated test account before wiring
AI-heavy flows.
