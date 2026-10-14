# Content Creator Roadmap

Reviewed: 2026-10-14

This is a future roadmap, not a current feature commitment. Quote extraction and reel generation should be delivered as separate milestones because their workflows, risks, and costs differ substantially.

## Milestone A: Sermon quote extraction

### Proposed user experience

1. Open a completed sermon study and choose **Find quote candidates**.
2. Review exact transcript excerpts with speaker/source attribution and timestamps when the transcript contains reliable timing data.
3. Compare every candidate with surrounding transcript context.
4. Edit the selected excerpt without silently changing the source quotation; distinguish a verbatim quote from an adapted social caption.
5. Choose a simple branded post layout, preview it, and export text or an image.

The product must never present a model-written paraphrase as the speaker's exact words. Each quote should retain transcript offsets, source media ID, timestamp range, confidence/review status, and the reviewing user's ID.

### Architecture and data

- Add transcript segments with `start_ms`, `end_ms`, speaker label when known, text, and source provenance.
- Add quote candidates linked to immutable transcript segment IDs, plus separate reviewed/export text.
- Extend the AI boundary with a strict quote-candidate schema. The model selects spans; application code verifies that verbatim text exists in the source transcript.
- Queue extraction, make it idempotent, record prompt/model versions, and keep human review mandatory before export.
- Store export metadata and attribution separately from sermon notes.

Reliable timestamps are a prerequisite for “jump to moment” links and for the reel milestone. OpenAI's current word timestamp option is documented for `whisper-1` with `verbose_json`; the current Luma transcription path returns plain JSON and does not preserve timed segments. Timestamp support therefore needs a deliberate provider/model decision and migration.

### Estimate

- Timed transcript data model and ingestion: 24–40 hours
- Quote selection, verification, review UI, and tests: 40–64 hours
- Optional branded static-image export: 24–40 hours
- Total quote milestone: **64–104 hours**, or **88–144 hours** with image export

## Milestone B: Customizable sermon reels

### Proposed user experience

1. Start from an approved quote or select a permitted media time range.
2. Preview the clip with synchronized captions.
3. Choose 9:16, 1:1, or 16:9; set caption style, brand colors, logo placement, and safe-area positioning.
4. Adjust clip boundaries and caption timing.
5. Render asynchronously, review the result, and download/export it.

### Technical architecture

- Direct-to-object-storage uploads using short-lived signed URLs; never route large production uploads through a web worker.
- Virus/type validation, media probing, duration/codec checks, and an explicit rights attestation before processing.
- A media asset table, timed transcript segments, clip projects, style/version data, render jobs, and immutable export records.
- A dedicated render queue isolated from ordinary web and sermon-analysis workers.
- FFmpeg containers for an infrastructure-controlled MVP, or a managed video platform such as Mux/Cloudinary when operational simplicity and scalable delivery outweigh vendor cost.
- Webhook verification, idempotent render callbacks, progress states, cancellation, retry limits, cleanup jobs, and observability.
- Caption formats such as WebVTT/SRT internally, with captions burned into social exports where required.

FFmpeg provides the most control and avoids per-render SaaS lock-in, but requires container hardening, autoscaling, codec maintenance, and resource isolation. A managed platform reduces operations and can provide storage, clipping, encoding, and delivery. Mux currently prices video by input, stored, and delivered minutes and lists basic storage starting at $0.0024 per minute per month; all rates and export capabilities must be validated during vendor selection: <https://www.mux.com/pricing>.

### Costs

Model costs separately:

- Transcription per input minute. OpenAI currently lists `gpt-4o-mini-transcribe` at an estimated $0.003/minute: <https://developers.openai.com/api/docs/pricing>.
- Original and derived media storage, lifecycle/egress, and backups.
- CPU/GPU render minutes or managed encoding/clip-generation charges.
- CDN/download bandwidth, especially for repeated previews and exports.
- AI quote-selection tokens and moderation/safety checks.
- Monitoring, failed-job retention, and support.

Run a 100-sermon cost pilot before setting plan limits. Apply short retention to temporary sources and failed renders; retain final exports only according to the approved product policy.

### Copyright and permissions

- Require uploaders to confirm ownership or permission to transcribe, clip, caption, brand, and redistribute the media.
- Preserve speaker, church, sermon title, source URL, and timestamp attribution.
- Provide deletion/takedown handling and propagate deletion to derived files.
- Do not download or separate audio from YouTube URLs. YouTube's published developer guidance prohibits offline downloads and separating audio/video tracks through an API client: <https://developers.google.com/youtube/terms/developer-policies-guide>.
- Obtain legal review for commercial use, guest speakers, worship music, licensed footage, Bible-translation quotations, and third-party trademarks.

### Estimate

- Media/timestamp foundation (if not completed for quotes): 32–56 hours
- Clip editor and responsive preview: 56–88 hours
- Render pipeline, captions, formats, retries, and cleanup: 64–104 hours
- Storage/CDN integration, security, observability, and tests: 40–64 hours
- Total reel MVP: **192–312 hours**
- Production hardening, templates, team branding, and export integrations: an additional **80–160 hours**

## Dependencies and suggested phase

Quote extraction depends on a verified sermon-processing pipeline, stable structured outputs, transcript provenance, and timestamped segments. Reel generation additionally depends on a production media store, dedicated workers, rendering infrastructure, content retention, and rights policies.

Suggested sequence:

1. Finish and verify the existing sermon study and authorized transcription workflow.
2. Add timed transcripts and quote extraction as a standalone creator milestone.
3. Validate rights, demand, unit economics, and export formats with Randy.
4. Build reels in a later creator-tools phase. Do not include it in the current sermon-study phase.

## Decisions needed from Randy

- Is the first creator deliverable reviewed quote text, static quote cards, or both?
- Must quotes be word-for-word only, or can users publish clearly labeled adaptations?
- Which social aspect ratios and brand controls are essential for the first reel version?
- Who owns the source media, and what proof/attestation is required?
- How long should source media, drafts, and exports be retained?
- Is a managed video vendor acceptable, and what monthly pilot budget is approved?
