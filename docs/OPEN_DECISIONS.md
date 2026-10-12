# Open Decisions

The following choices are intentionally unresolved. They must be approved before the associated phase is implemented.

## Product and theology

- Supported sermon sources and transcript fallback behavior
- The theological tradition or statement of faith that should guide generated explanations
- Citation requirements, source ranking, pastoral disclaimers, and human-review policy
- How church leaders, individual learners, and possible shared libraries differ
- Final terminology for LUMA versus LumaWord in product copy

## Privacy and content

- Sermon/transcript retention periods and user deletion/export behavior
- Whether source media is downloaded, streamed, or transcript-only
- Private-storage provider, encryption expectations, and geographic data requirements
- Copyright/takedown handling for third-party sermon content

## Accounts and mobile

- Whether email verification is required before study features are used
- Whether two-factor authentication is required or optional
- Native iOS token issuance, expiration, refresh, revocation, and device management
- Social sign-in providers, if any

## Plans and billing

- Free and Premium prices
- Usage limits and exact feature entitlements for each plan
- Trial, refund, cancellation, tax, and regional availability policies
- Stripe customer portal and webhook operating model

## Operations

- Production hosting, managed MySQL, Redis, object storage, mail, and DNS providers
- Queue concurrency and retry policy once AI workloads exist
- Monitoring, analytics, consent, support, and incident-response tools
- Admin roles and audit-log retention
