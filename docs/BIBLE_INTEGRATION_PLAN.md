# Luma Bible Integration Plan

Research reviewed: October 14, 2026.

## Recommendation

Use the YouVersion Platform as Luma's primary provider for licensed Bible content, with a Laravel server-side integration rather than direct browser calls. YouVersion provides official access to more than 1,000 Bible versions, including the NIV, through a REST API and JavaScript/React SDKs. Access requires an application key and acceptance of the license attached to each translation. The API only returns content authorized for the registered application.

Keep a provider-neutral boundary in Luma so public-domain Scripture can be served locally and another licensed provider can be substituted without changing the reader UI. API.Bible is the strongest secondary option: its current plans include public-domain, Creative Commons, and selected copyrighted Bibles, but the free tier is strictly non-commercial and its paid plan starts at $29 per month.

Recommended rollout:

1. Obtain written approval for Luma's exact use case from YouVersion and each relevant copyright holder before implementation.
2. Launch with the World English Bible (WEB), whose publisher explicitly dedicates the text to the public domain, plus any provider translations actually licensed to Luma.
3. Add KJV only after confirming the launch territories. It is public domain in the United States, but the Authorized Version remains subject to Crown rights in the United Kingdom.
4. Add NIV, ESV, NLT, and NKJV only after the applicable API/provider license is accepted and any separate publisher permission is documented.

## Translation and licensing matrix

| Translation | Proposed status | Requirements before launch |
| --- | --- | --- |
| World English Bible (WEB) | Safe first-party/local option | Use an official, unmodified eBible.org distribution; retain translation identity and attribution. The text is public domain, while the name is a trademark used to identify faithful copies. |
| King James Version (KJV) | Optional, territory-dependent | Verify the source edition and deployment territories. The text is public domain in the US, but publication rights are administered by the Crown's patentee in the UK. |
| Berean Standard Bible (BSB) | Candidate open option | Confirm the exact version metadata and license returned by the selected provider before ingesting or caching it. |
| NIV | Licensed option, not bundled | Prefer YouVersion Platform if its license is granted to Luma. Biblica says app/website use requires written permission and specifically requires AI-powered products to discuss licensing with Biblica. |
| ESV | Licensed option, not bundled | Crossway's API permits non-commercial website use within its limits: 5,000 queries/day and no more than 500 locally stored verses or half a book. A commercial Luma offering or broader storage requires separate permission. |
| NLT | Licensed option, not bundled | Obtain permission from Tyndale or access under a provider license that expressly covers Luma. Tyndale's general quotation rules are not permission to operate a full Bible reader, particularly if monetized. |
| NKJV | Licensed option, not bundled | Obtain rights from Thomas Nelson/HarperCollins Christian Publishing or a provider license that expressly includes NKJV display in Luma. Do not treat its relationship to KJV as public-domain status. |

An API key alone is not proof that every translation is licensed for every use. At runtime, Luma should expose only versions returned as licensed for its application and render each provider-supplied copyright statement exactly as required.

## Authentication, licensing, and content handling

- Store provider credentials only in server environment secrets. Never expose an application key in the React bundle.
- Proxy catalog and passage requests through versioned Laravel endpoints with authentication, throttling, timeouts, retries for transient failures, and structured error responses.
- Record the provider, provider version ID, license identifier/version, copyright statement, attribution HTML/text, permitted territories, cache policy, and approval date for every enabled translation.
- Default copyrighted passages to short-lived response caching only when the provider license permits it. Do not persist whole copyrighted translations or include them in exports, prompts, backups, or search indexes without express rights.
- Preserve provider HTML and required fonts/styles when a license requires their use. Sanitize only through a strategy approved for the provider's known response format.
- Treat AI use as a separate licensed purpose. A license to display NIV or another translation in a reader does not automatically authorize sending that text to an AI model for sermon questions or devotionals.
- Add a kill switch per translation so content can be disabled immediately if a license expires or provider access changes.

## Reader experience

Add a `Bible` destination to authenticated navigation and use a shareable canonical URL such as:

`/bible/{translation}/{book}/{chapter}?verses=16-18`

Desktop and tablet:

- Sticky translation selector and book/chapter controls above the passage.
- Previous/next chapter actions and a direct reference field.
- Readable single-column passage with verse numbers, section headings, provider attribution, and a restrained maximum line length.
- Optional contextual panel for sermon notes that reference the open passage.

Mobile:

- Compact sticky control row with bottom sheets for translation, book, and chapter selection.
- Large touch targets, adjustable text size, strong focus states, and no horizontal scrolling.
- Preserve the canonical reference when changing translations; only the provider/version ID changes.
- Keep the current passage in the URL so refresh, back/forward navigation, and shared links work naturally.

Accessibility requirements include semantic headings, labelled selectors, keyboard navigation, visible focus, adequate contrast, screen-reader-friendly verse numbers, and user-controlled text sizing without layout breakage.

## Backend and data architecture

Introduce these boundaries when implementation is approved:

- `BibleProvider` contract for translation catalog, books, chapters, and passage retrieval.
- `YouVersionBibleProvider` for licensed remote content.
- `LocalBibleProvider` for approved public-domain source files.
- `BibleReferenceParser` to normalize references to USFM book identifiers and canonical start/end verse coordinates.
- `BiblePassageService` to enforce enabled translations, licensing metadata, caching policy, and provider failover.

Proposed records:

- `bible_translations`: internal key, provider, provider version ID, abbreviation, language, license metadata, attribution, availability, default flag, and cache policy.
- `scripture_references`: canonical book/chapter/verse range, original display text, parse status, and timestamps.
- A polymorphic or explicit relation from sermon notes to `scripture_references`, depending on whether devotionals and future content will share the same references.
- A user preference for default translation. The saved reference remains translation-neutral so switching versions never changes the passage.

Do not store provider passage text in sermon-note records. Store the normalized reference and resolve authorized text on demand. Public-domain text may be imported into indexed local tables or a versioned read-only data file after source integrity is verified.

## Sermon-note links and future AI features

During sermon processing, parse detected references such as `John 3:16-18` into canonical USFM coordinates and retain the original wording for display. Each reference becomes a link to the Bible reader with the user's preferred translation. Invalid or ambiguous references remain plain text until corrected.

For future sermon questions and devotionals:

- Use canonical references as the durable connection between notes and Scripture.
- Resolve passage text through `BiblePassageService` at request time.
- Send passage text to an AI provider only when the translation license explicitly permits machine-learning/AI processing; otherwise use an approved public-domain translation or send only the reference.
- Preserve translation attribution in generated views and distinguish Scripture text from AI-generated explanation.

## Effort and operating cost

Estimated engineering effort after licensing decisions:

| Workstream | Estimated hours |
| --- | ---: |
| Provider proof of concept, secrets, catalog, and license metadata | 12-18 |
| Backend provider contract, passage endpoints, caching, and failure handling | 20-30 |
| Responsive reader, selectors, URL state, attribution, and accessibility | 28-40 |
| Scripture-reference parsing and sermon-note links | 16-24 |
| Automated tests, responsive/browser QA, monitoring, and documentation | 16-24 |
| **Production-ready total** | **92-136 hours** |

An optional public-domain-only milestone using WEB could be delivered in approximately **40-60 hours**, including a responsive reader, local data import, navigation, and tests. It should be separately approved rather than added to the current branding task.

Ongoing costs:

- YouVersion: public documentation describes application keys, per-license acceptance, and rate limiting but does not publish a standard price. Confirm commercial terms, quotas, caching rights, and support directly during application review.
- API.Bible: currently advertises a $0 non-commercial starter tier with 5,000 calls/month and a $29+/month Pro tier with 150,000 calls/month; additional calls are available for purchase.
- Public-domain local hosting: normally only incremental database/CDN/backup cost, estimated at $0-$20/month at early Luma traffic, plus normal application monitoring.
- Publisher licenses: potentially separate or negotiated fees; no amount should be budgeted as zero until written terms are received.

## Decisions required from Randy

1. Will Luma remain entirely non-commercial, or will it use subscriptions, advertising, fundraising, or paid premium features?
2. Which launch countries must be supported? This affects KJV and publisher territory rights.
3. Which translations are launch requirements versus later additions: WEB, KJV, NIV, ESV, NLT, NKJV, or others?
4. Is a YouVersion integration acceptable, and may Luma show provider-required attribution and links?
5. Should Scripture be readable only online, or is offline access a future requirement?
6. May copyrighted Scripture be used in AI sermon questions/devotionals, or should AI features always use a public-domain translation?
7. Approve either the 40-60 hour public-domain milestone or the 92-136 hour licensed multi-version implementation after provider acceptance.

## Sources

- [YouVersion Platform overview](https://developers.youversion.com/)
- [YouVersion API usage and license acceptance](https://developers.youversion.com/api-usage)
- [YouVersion authentication and rate-limit behavior](https://developers.youversion.com/authentication)
- [YouVersion license API](https://developers.youversion.com/api/licenses)
- [API.Bible plans](https://scripture.api.bible/signup)
- [API.Bible terms and translation-specific licensing](https://api.bible/terms-and-conditions)
- [World English Bible copyright status](https://ebible.org/engwebp/copyright.htm)
- [eBible.org intellectual-property policy](https://ebible.org/legal.php)
- [Crossway ESV API terms and limits](https://api.esv.org/)
- [Biblica permissions, including apps and AI](https://www.biblica.com/permissions/)
- [Tyndale NLT permissions](https://www.tyndale.com/permissions)
- [UK government copyright guidance for the King James Bible](https://www.gov.uk/government/publications/copyright-notice-duration-of-copyright-term/copyright-notice-duration-of-copyright-term)
