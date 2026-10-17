# Sermon Link Integration Review

Reviewed: 2026-10-17

## Current behavior

Luma accepts a public HTTP or HTTPS URL, rejects local/private-network destinations, normalizes the URL, identifies common providers, and preserves it on the sermon. General URL submissions remain in `awaiting_transcript`. A supported YouTube video link can instead queue official caption retrieval when its owner has connected the matching channel.

The supported processing paths are:

1. Paste an authorized transcript. Luma stores it privately and queues structured notes generation.
2. Upload authorized audio or video, either as a new sermon or as the fallback on a saved link. Luma stores it privately, queues OpenAI transcription, deletes the media after a successful transcript, and then queues notes generation in the same job.
3. Retrieve an authorized caption for a connected, channel-owned YouTube video and queue notes generation without an OpenAI transcription request.

The UI states the authorization boundary before submission and offers paste and upload fallbacks directly on the saved sermon.

## Implemented owner-authorized YouTube workflow

Luma now supports an optional owner-authorized YouTube connection. A user connects the Google account for a YouTube channel they control. For a supported video link owned by that exact connected channel, a background job verifies channel ownership, lists caption tracks through the YouTube Data API, downloads one authorized caption, stores the provider video/caption audit identifiers, and passes the transcript to the existing notes pipeline. OAuth access and refresh tokens are encrypted at rest.

Supported link shapes are `youtube.com/watch?v=...`, `youtu.be/...`, `youtube.com/shorts/...`, `youtube.com/live/...`, and official `youtube.com/embed/...` or `youtube-nocookie.com/embed/...` links. YouTube channel, playlist, search, malformed, and lookalike-domain URLs are rejected.

Arbitrary public YouTube links still cannot be processed automatically. If the video is not owned by the connected channel, captions are unavailable, authorization expires, or provider access fails, Luma requests a transcript. The user can paste one or upload an authorized audio/video file on the same sermon, preserving the original link. Luma does not scrape pages, download YouTube media, bypass access controls, or use unofficial caption endpoints.

The UI distinguishes saved links, caption retrieval, transcript required, transcription, note generation, completion, and failure. Provider retrieval and notes generation are deduplicated and rate limited.

## Production configuration

1. Enable YouTube Data API v3 in a Google Cloud project.
2. Create an OAuth 2.0 Web application client and register the exact production redirect URI: `https://lumaword.easytechinfo.net/api/v1/youtube/callback`.
3. Configure `YOUTUBE_CLIENT_ID`, `YOUTUBE_CLIENT_SECRET`, and `YOUTUBE_REDIRECT_URI` in the production secret store. Do not commit them.
4. Configure the OAuth consent screen for the `https://www.googleapis.com/auth/youtube.force-ssl` scope and complete any Google verification required for production users.
5. Run the database migrations and keep a supervised queue worker running. The worker must use shared cache locking for reliable job uniqueness, and the queue reservation timeout must remain longer than the job timeout.

The connector uses `videos.list` (1 quota unit), `captions.list` (50 units), and `captions.download` (200 units) once per normal successful retrieval. Limits remain configurable. No OpenAI transcription call is made when an authorized provider caption succeeds.

## Provider findings

### YouTube

The official YouTube Data API does not provide captions for arbitrary public videos. `captions.list` requires OAuth 2.0, and `captions.download` requires the signed-in user to have permission to edit the video. A download call costs 200 quota units. YouTube's developer policies also prohibit offering video downloads, separating audio, or modifying YouTube audiovisual content through an API client.

Official references:

- <https://developers.google.com/youtube/v3/docs/captions/download>
- <https://developers.google.com/youtube/v3/guides/implementation/captions>
- <https://developers.google.com/youtube/terms/developer-policies-guide>

Implemented: the optional “Connect your YouTube channel” OAuth flow is limited to pastors or churches retrieving captions from videos their connected channel controls. Luma does not promise transcription for arbitrary YouTube URLs and does not use scraping or unofficial caption endpoints.

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

1. Complete Google OAuth consent-screen configuration, verification, and a YouTube API compliance review before enabling the connector for general production users.
2. Production-harden queue supervision, monitoring, and retention rules, then run one owner-authorized end-to-end retrieval after credentials are configured.
3. Decide how long provider captions and audit identifiers may be retained and document the takedown/deletion process.
4. Add a provider contract only when a second owner-authorized connector is approved.
5. Do not integrate a third-party “any public URL” transcript vendor until it provides written confirmation of platform authorization, content-rights responsibilities, retention, redistribution, and commercial-use terms.

## Remaining estimated effort

| Milestone | Estimate |
| --- | ---: |
| Production queue and upload-flow hardening, monitoring, and live verification | 16–28 hours |
| Google OAuth verification, compliance review, and live owner-channel verification | 8–16 engineering hours plus review time |
| Vimeo owner-authorized text-track connector | 32–56 hours |
| Provider abstraction after an approved first connector | 12–20 hours |
| Legal/vendor review and API proof of concept for a licensed third-party source | 8–16 engineering hours plus legal review |

Ongoing costs include transcription usage, database/queue capacity, private object storage, logs, and any provider plan. Google quota is not a substitute for content permission. Paid vendor/API access is not a blanket right to cache or redistribute sermon text.

## Remaining decisions and limitations

- Google OAuth consent-screen configuration, verification, and YouTube API compliance review must be completed before general production use.
- Vimeo remains a separate, unimplemented owner-authorized connector.
- Whether Luma may store provider captions and for how long.
- The user's required rights attestation and takedown/deletion process.
- Approved vendor budget and legal review for any third-party transcript service.
- Production queue host, worker supervision, monitoring, and media-retention policy.
