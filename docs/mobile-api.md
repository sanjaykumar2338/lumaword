# LUMA mobile API contract

Base URL: `https://lumaword.app/api/v1`. Authenticated routes use the bearer
token returned by the mobile login/register endpoints. Errors are JSON with a
`message` and optional `errors` object.

## Native notifications

- `POST /mobile/device-tokens` with `provider`, `token`, `platform` (`android` or
  `ios`), and optional `app_version`; returns `201` for a new token and `200`
  for an idempotent update. The token is encrypted and scoped to the user.
- `DELETE /mobile/device-tokens` with `provider` and `token`; removes only the
  current user's matching token.
- `GET|PUT /mobile/notification-preferences` reads or replaces the user-level
  flags `enabled`, `sermon_processing`, `devotional_reminders`, and
  `subscription_updates`.

Firebase delivery remains disabled unless `FIREBASE_NOTIFICATIONS_ENABLED=true`,
`FIREBASE_PROJECT_ID`, and a Firebase service-account JSON credential are
configured. The gateway uses FCM HTTP v1 and exchanges the service account for
a short-lived OAuth token; the legacy server-key endpoint is not used. Provider
credentials never reach the mobile client. Notification payloads contain `type`, `title`, `body`, and may
contain `route` and `route_arguments`. Supported tap routes are `/sermons/{id}`,
`/devotionals/{id}`, `/reading-plans/{id}`, and `/entitlements`.

## Account deletion

`DELETE /user` requires `{ "confirmation": "DELETE" }`. It permanently removes
the account, bearer tokens, subscriptions, device tokens, preferences, and
owned application data. Success is `200` with `{ "message": "Account
permanently deleted." }`; missing or incorrect confirmation is `422`.

## Sermons

Existing owner-authorized contracts are unchanged:

- `PATCH /sermons/{id}` with `{ "title": "..." }` returns `200` and `data`.
- `POST /sermons/{id}/process` retries processing and returns the refreshed
  sermon resource. It is safe to call while processing is active.
- `DELETE /sermons/{id}` permanently deletes the sermon and returns `204`.

## Mobile purchases and entitlements

- `POST /mobile/purchases/verify` accepts `store` (`app_store` or `play_store`),
  `product_id` (`luma_monthly` or `luma_annual`), `transaction_id`, and opaque
  `verification_data`.
- `GET /mobile/purchases/status` returns the same `data.membership` and
  `data.usage` shape as `GET /entitlements`.

Configure the monthly and annual store product IDs in `.env` (the four
`*_PRODUCT_ID` settings in `.env.example`) and use those same IDs in the
Flutter build flags. The backend normalizes them to the canonical
`luma_monthly` and `luma_annual` entitlements. Native billing is disabled by
default; Flutter build flags and store credentials must be enabled together.
Mobile membership responses report the verified provider status and expiry
date, while Stripe/Cashier remains the independent website entitlement source.

Verification is server-side, replay-safe by `(store, transaction_id)`, and
mobile purchases are merged into the existing entitlement source of truth. The
Apple adapter uses the App Store Server API, signed JWS transaction data, and a
configured Apple certificate root; it does not use deprecated `verifyReceipt`.
The Google adapter uses `purchases.subscriptionsv2.get` with a server-side
OAuth bearer credential. Both store adapters fail closed with a validation error
until their credentials and enable flags are configured. Stripe/Cashier remains
an independent source and its checkout/portal contracts are unchanged.
