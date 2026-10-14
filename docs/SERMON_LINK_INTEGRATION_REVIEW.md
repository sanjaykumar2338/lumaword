# Sermon Link Integration Review

Reviewed: 2026-10-14

## Current behavior

Luma accepts a public HTTP or HTTPS URL, rejects local/private-network destinations, normalizes the URL, identifies common providers, and stores it with the `awaiting_transcript` status. It does **not** open the page, download media, scrape captions, or request a provider transcript. No processing job is dispatched for a URL-only sermon.

The supported processing paths are:

1. Paste an authorized transcript. Luma stores it privately and queues structured notes generation.
2. Upload authorized audio or video. Luma stores it privately, queues OpenAI transcription, deletes the media after a successful transcript, and then queues notes generation in the same job.

The UI now states this before a link is submitted and again on the sermon page. A URL-only sermon presents two next actions: paste a transcript or start a new authorized-media upload.

## Provider findings

### YouTube

The official YouTube Data API does not provide captions for arbitrary public videos. `captions.list` requires OAuth 2.0, and `captions.download` requires the signed-in user to have permission to edit the video. A download call costs 200 quota units. YouTube's developer policies also prohibit offering video downloads, separating audio, or modifying YouTube audiovisual content through an API client.

Official references:

- <https://developers.google.com/youtube/v3/docs/captions/download>
- <https://developers.google.com/youtube/v3/guides/implementation/captions>
- <https://developers.google.com/youtube/terms/developer-policies-guide>

Recommendation: add an optional “Connect your YouTube channel” OAuth flow only for pastors or churches retrieving captions from videos they control. Do not promise transcription for arbitrary YouTube URLs, and do not use scraping or unofficial caption endpoints.

### Vimeo

Vimeo exposes text-track resources through its authenticated API. Access depends on the video's visibility, the token, account permissions, and the fields/products enabled for that account. Vimeo's newer AI video endpoints require the `ai` OAuth scope and an Enterprise account.

Official references:

- <https://developer.vimeo.com/api/upload/texttracks>
- <https://developer.vimeo.com/api/reference/response/text-track/3.4>
- <https://developer.vimeo.com/api/reference/videos>

Recommendation: treat Vimeo as a separate owner-authorized connector, not as a generic public-link transcript source.

### Uploaded media and OpenAI

The safest provider-neutral route is the existing authorized upload flow. OpenAI currently accepts common audio/video container formats up to 25 MB per transcription request. Larger sources require approved compression or chunking. `gpt-4o-mini-transcribe` is currently listed at an estimated $0.003 per minute; pricing must be rechecked before launch.

Official references:

- <https://developers.openai.com/api/docs/guides/speech-to-text>
- <https://developers.openai.com/api/docs/pricing>

## Recommended implementation sequence

1. Keep URL storage as a reference-only feature with the current “Needs transcript” state.
2. Production-harden pasted transcript and authorized upload processing, including a supervised queue worker, monitoring, retention rules, and a real end-to-end test.
3. If Randy needs channel-owned YouTube captions, obtain approval for Google OAuth and complete a YouTube API compliance review before implementation.
4. Add a provider contract such as `TranscriptProvider` only when the first authorized connector is approved. Store provider account IDs/tokens encrypted and keep provider caption IDs and permission/audit metadata.
5. Do not integrate a third-party “any public URL” transcript vendor until it provides written confirmation of platform authorization, content-rights responsibilities, retention, redistribution, and commercial-use terms.

## Estimated effort

| Milestone | Estimate |
| --- | ---: |
| Production queue and upload-flow hardening, monitoring, and live verification | 16–28 hours |
| YouTube owner OAuth, caption discovery/download, token lifecycle, audit UI, tests | 40–64 hours |
| Vimeo owner-authorized text-track connector | 32–56 hours |
| Provider abstraction after an approved first connector | 12–20 hours |
| Legal/vendor review and API proof of concept for a licensed third-party source | 8–16 engineering hours plus legal review |

Ongoing costs include transcription usage, database/queue capacity, private object storage, logs, and any provider plan. Google quota is not a substitute for content permission. Paid vendor/API access is not a blanket right to cache or redistribute sermon text.

## Approval needed

- Whether owner-authorized YouTube and/or Vimeo connections are a priority.
- Whether Luma may store provider captions and for how long.
- The user's required rights attestation and takedown/deletion process.
- Approved vendor budget and legal review for any third-party transcript service.
- Production queue host, worker supervision, monitoring, and media-retention policy.
