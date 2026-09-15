# We Offer Wellness: traffic, SEO and sales audit

**Audit date:** 15 September 2026  
**Properties:** GA4 `properties/422680391` (`www.weofferwellness.co.uk`), Search Console URL/domain properties  
**Repositories:** Frontend and Backend, branch `fix/order-notification-delivery`

## Executive diagnosis

The evidence does not support a single “not enough supply” explanation. The primary failure is a leaky measurement and discovery-to-booking funnel:

1. **Search visibility is weak at scale.** Search Console reports 283,602 impressions, only 4,202 clicks (1.48% CTR), and weighted average position 36.35 for the available 16-month window. Most impressions are below page one. UK desktop is particularly weak (position 43.48, CTR 0.68%) while UK mobile is materially better (position 22.12, CTR 3.42%).
2. **The purchase signal is not trustworthy.** GA4 reports 3,560 conversions but only 41 transactions. `/search` has 313 conversions and zero transactions; Organic Shopping has 301 conversions and zero transactions. Those conversions are not a sales KPI and are almost certainly a mixture of CTA, engagement, account or duplicate events.
3. **Real sales are sparse and concentrated.** Across 40,195 sessions and 34,561 users (15 Sep 2024–14 Sep 2026), there were 41 transactions and £1,509.73 recorded revenue: an observed transaction rate of about 0.10% and £0.04 revenue per session. Organic Search delivered 12 of those transactions from 10,963 sessions; the home page delivered 21.
4. **Demand exists for specific, localised clusters.** Search Console demand is strongest around 9D breathwork, sound baths/healing, QHHT/quantum healing, couples yoga, chakra/energy work, chi nei tsang, cacao/shamanic work and “near me”, cost and London/UK modifiers. Existing pages often rank between positions 8–30, so better intent matching, content and conversion UX can unlock demand without manufacturing thousands of doorway pages.
5. **The sitemap system is inconsistent.** The live sitemap index contains 11 child files, while application config expects additional `sitemap-pages.xml`, modality-location, type-location and events files. Live location/near-me sitemaps contain `null` URLs. Modality-location, type-location and events requests timed out during the audit. This can waste crawl budget and conceal otherwise useful landing pages.
6. **Supply is a secondary, segment-specific issue.** UK users are the only geography producing meaningful transactions. US, Singapore, China, Germany and other desktop segments create sizeable traffic with very low engagement and little/no revenue. The catalogue should clearly separate bookable UK in-person supply from online/distance supply and avoid presenting an unavailable local result as a bookable match.

This is a **trust, intent and availability problem before it is a volume problem**. Fix measurement first, then improve the high-impression pages and availability-led booking path.

## Data map

### GA4 two-year window (15 Sep 2024–14 Sep 2026)

| Metric | Value | Interpretation |
|---|---:|---|
| Sessions | 40,195 | modest total demand for a two-year marketplace |
| Users | 34,561 | high proportion of one-session visitors |
| Engaged sessions | 22,395 | aggregate engagement rate about 55.7% |
| Reported conversions | 3,560 | not safe to call sales |
| Transactions | 41 | the only reliable sales-shaped count in this report |
| Purchase revenue | £1,509.73 | low, with a small number of purchases |

Channel evidence:

- Direct: 18,708 sessions, 14 transactions, £177.39.
- Organic Search: 10,963 sessions, 12 transactions, £835.34; engagement rate 73.14%.
- Organic Social: 4,610 sessions, 9 transactions, £209.
- Email: 2,125 sessions, 2 transactions.
- Referral: 1,238 sessions, 3 transactions, £258.
- Unassigned: 2,024 sessions, 1 transaction and low engagement (35.52%).

The organic audience is comparatively engaged and generates the highest revenue per transaction-bearing channel, but ranking and click-through are too low. Direct traffic is large but weakly monetised, suggesting brand browsing, repeat non-buying visits, untagged campaigns, or attribution loss.

Top landing-page clues:

- `/` generated 12,331 sessions, 21 transactions and £514.
- `/search` generated 1,348 sessions and zero transactions despite 313 reported conversions.
- 1,337 `(not set)` landing sessions had only 2.39% engagement; investigate SPA/session attribution and consent timing.
- High-interest product pages include initial food intolerance (659 sessions, 83.61% engagement, no transaction), three gong bath sessions (554, 3 transactions, £60), QHHT (335, no recorded revenue), Shibashi Tai Chi/Qigong (327, 92.05% engagement), Our Vibe retreat (267, 3 transactions, £220.50), private gong bath (97, 2 transactions, £219), Psych-K (92, 1 transaction, £250), and Our Vibe book (62, 1 transaction, £18.89).

Geography/device evidence:

- UK mobile: 10,308 sessions, 22 transactions, £666.39.
- UK desktop: 8,164 sessions, 12 transactions, £812.
- US desktop: 5,613 sessions, 1 transaction.
- Singapore desktop: 5,014 sessions, 0 transactions and 25.63% engagement.
- China desktop: 2,789 sessions, 0 transactions.
- Germany desktop: 1,824 sessions, 0 transactions and 20.94% engagement.

Treat the non-UK desktop volume as an acquisition/quality and availability question, not evidence that every country needs hundreds of local pages. Offer online/distance inventory and currency/time-zone clarity where supply exists; otherwise reduce irrelevant exposure.

### Search Console (retention-limited window: 15 May 2025–12 Sep 2026)

Search Console cannot provide a full two years through this API; its retained data is roughly 16 months. In that window: 4,202 clicks, 283,602 impressions, 1.48% CTR, average position 36.35.

The trend improved from positions around 47–48 in May–June 2025 to roughly 22 in November–December 2025, then became volatile: clicks and impressions rose sharply in June–August 2026 while average positions remained around 30–35. This is an opportunity, not proof of a conversion win.

Highest-value queries include `9d breathwork near me` (98 clicks, 3,765 impressions, position 7.31), `sound bath near me` (40, 1,169, position 16.98), `9d breathwork london` (27, 445, position 5.76), `couples yoga london` (24, 325, position 7.93), `9d breathwork uk` (15, 464, position 5.52), `cacao ceremony near me` (13, 400, position 17.52), `chi nei tsang london` (11, 416, position 14.32), `gong bath near me` (11, 324, position 27.15), `qhht near me` (7, 787, position 10.22), and `silent counselling` (6, 510, position 10.04). Cost and availability intent is visible in `9d breathwork cost`, `distance reiki prices uk`, and `psych-k training online`.

Pages with the clearest page-one/page-two upside:

| Page | Impressions | Clicks | CTR | Avg position |
|---|---:|---:|---:|---:|
| QHHT product | 12,938 | 107 | 0.83% | 15.84 |
| Meet the team | 6,698 | 228 | 3.40% | 14.18 |
| Psych-K | 2,951 | 9 | 0.30% | 18.42 |
| What is a wellness event | 2,444 | 3 | 0.12% | 12.28 |
| Food intolerance consultation | 2,188 | 28 | 1.28% | 25.75 |
| Couples yoga | 2,157 | 86 | 3.99% | 10.16 |
| Chi Nei Tsang | 1,915 | 23 | 1.20% | 21.13 |
| 9D South Shields workshop | 1,665 | 26 | 1.56% | 12.25 |
| Inner child healing | 1,438 | 19 | 1.32% | 28.76 |
| Mind-shifting | 1,425 | 29 | 2.04% | 17.40 |
| Shamanic drum workshop | 1,347 | 43 | 3.19% | 14.39 |
| Chakra cleansing | 1,178 | 46 | 3.90% | 17.81 |
| Kundalini 1:1 | 1,108 | 22 | 1.99% | 29.18 |
| Three gong bath sessions | 807 | 23 | 2.85% | 28.31 |

Product snippets generated 93,868 impressions but only 1,224 clicks (1.30%) at average position 38.79. Review snippets produced zero clicks from 14 impressions. Product structured data is therefore not a substitute for better titles, descriptions, inventory proof and reviews.

## Supply versus demand

There is real demand, but the marketplace currently mixes four different jobs into one catalogue: local appointment discovery, scheduled events, online/distance sessions and products/books. A visitor searching “near me” needs distance, next available time, price and a bookable provider; a visitor searching “9D breathwork cost” needs an explanation and comparable offers; a visitor searching QHHT may need trust, practitioner credentials and a clear consultation path. Showing the same card and CTA to all four intents suppresses sales.

The likely supply gaps are not “we need every modality everywhere”. They are:

- insufficient bookable results in the locations that already generate impressions;
- no clear fallback to online when a local result is unavailable;
- thin or generic pages for high-interest modalities and cost/near-me intent;
- weak proof (reviews, practitioner identity, duration, price, next availability, cancellation terms) above the booking CTA;
- products and offerings sharing URLs/cards/labels, causing expectation mismatch.

Do not create manipulative “dark psychology” experiences. Interpret the observed psychology/social-network interest as a need for ethical self-development discovery: Psych-K, QHHT, inner-child work, mindfulness, nervous-system regulation, counselling and coaching should be grouped with plain-language outcomes, safety boundaries and practitioner credentials. Personalise with consented views, saves, searches and dwell/linger signals, but never hide alternatives, manufacture scarcity, or pressure a user because they hesitated.

## Instrumentation and analytics fixes

1. Define one canonical GA4 `purchase` event. Require a stable `transaction_id`, `value`, `currency=GBP`, item ID, item name, provider ID and booking/checkout source. Deduplicate retries and success-page reloads server-side.
2. Remove `conversion` status from generic search, card-view, account, scroll and CTA events. Keep them as funnel events (`view_item`, `search`, `select_item`, `begin_checkout`, `add_payment_info`, `purchase`) and mark only `purchase` (and explicitly defined qualified lead events) as conversions.
3. Reconcile Stripe/order records to GA4 daily: orders, gross/net value, refunds, currency, transaction ID, source/medium and landing page. Alert when GA4 purchase count differs from orders by more than 5%.
4. Ensure one public GA4 property/tag. The code has multiple tag paths and IDs (`G-MZMQNETBYH` in the Blade head and `G-7G10DWBZY6` in an Inertia default layout; optional GTM is separate). Verify which layout is served, remove duplicate page views, and use Consent Mode v2 before analytics cookies. The app explicitly disables automatic page views in one layout and emits SPA page views manually; test one page view per navigation.
5. Add funnel dimensions: `catalogue_type` (offering/product/event), modality, location, online flag, availability state, price band, provider, device, country, query intent and experiment variant. Capture `no_results` and `availability_fallback`.
6. Treat `(not set)` landing traffic and unassigned traffic as instrumentation defects until proven otherwise. Preserve UTMs through Inertia, payment redirects and consent changes.

## Sitemap and crawl audit

The application has a substantial sitemap service and a daily SEO redirect job followed by a 02:15 sitemap ping. The scheduler is present, but the live output is not reliable enough to trust:

- live index: static, schedules, modalities, types, offerings, locations, near-me, online, by-need, practitioners and guides;
- config also expects `sitemap-pages.xml`, modality-location, type-location and events;
- live locations contained `/locations/null`;
- live near-me contained modality URLs ending in `/null`;
- modality-location, type-location and events timed out in live requests;
- static files have large last-modified batches, so `lastmod` should reflect actual source changes rather than every regeneration;
- sitemap URLs must be canonical, HTTP 200, indexable, non-redirecting and backed by at least one real result.

Recommended sitemap structure:

```text
/sitemap.xml                         # only the canonical sitemap index
/sitemaps/static.xml                 # true evergreen pages
/sitemaps/offerings-0001.xml         # chunked, canonical offering URLs
/sitemaps/products.xml               # books/physical products, separate schema/UX
/sitemaps/events-0001.xml            # future/scheduled events only
/sitemaps/modalities.xml             # modality hubs with live supply
/sitemaps/locations.xml              # locations with live supply
/sitemaps/modality-location-0001.xml # only modality+location intersections with supply
/sitemaps/online.xml                 # online/distance inventory
/sitemaps/practitioners.xml           # public practitioner profiles
/sitemaps/guides.xml                  # useful editorial guides
```

Do not put `/search`, carts, checkout, account pages, null slugs, empty location intersections, redirects or soft-404s into XML. The live robots rule disallowing `/search`, `/cart`, `/checkout`, `/account`, `/admin` and `/dashboard` is appropriate for non-indexable utility pages; ensure no sitemap URL is covered by those rules. Add a CI/cron validator that fetches every child sitemap, rejects null/duplicate URLs, checks status/canonical/noindex, records response time and fails the job on timeouts. Submit the canonical index through Search Console; do not rely on pinging every child URL.

## Structured data and on-page SEO

The live homepage, offering pages and landing pages emit JSON-LD, but coverage is inconsistent. A live request to the encoded QHHT `/products/...` URL returned a 404 canonical and no JSON-LD, while the canonical 9D and couples-yoga routes returned product/service, local-business and breadcrumb data. This indicates slug/redirect coverage needs testing, not that every page lacks schema.

For each canonical offering page, emit one coherent graph containing `BreadcrumbList`, `Service` or `Product` (not both unless genuinely applicable), `Offer` with price/currency/availability, provider `Person`/`Organization`, `areaServed`, `availableChannel` (online versus in-person), and `AggregateRating` only when real reviews exist. For events add `Event` with start/end date, location or virtual location and `offers`. For products/books use `Product` and `Offer`, separate from service cards. Add FAQ schema only for visible, useful FAQs. Validate rendered HTML (not only Vue source) in Rich Results Test and URL Inspection.

Improve titles/descriptions around the exact demand modifiers surfaced by GSC: modality + location, “near me”, cost/price, online/distance, duration, audience (couples/prenatal/family) and next availability. Rewrite the QHHT, Psych-K, food-intolerance, inner-child, chi-nei-tsang and three-gong-bath pages first; their impression-to-click rates show the largest immediate upside.

Google Trends is a directional research tool rather than a sales forecast. Use the UK Explore view to compare `breathwork`, `sound bath`, `reiki`, `QHHT`, `Psych-K`, `cacao ceremony`, `inner child healing` and `nervous system regulation` by region and season, then validate each candidate against the site’s own Search Console impressions and real supply. The official Trends workflow supports comparing terms, regions, related searches and exports ([Google Trends](https://trends.google.com/trends/explore?geo=GB); [Trends help](https://support.google.com/trends/answer/6248105?hl=en-GB)). Current editorial coverage also points toward repeatable sound healing, gentle yoga, reiki and breathwork/nervous-system regulation, but this is market context, not a substitute for the first-party data.

## Landing-page backlog

Build only where there is supply or a credible online alternative; put thin/no-supply combinations out of the index.

**P0 (existing demand, improve now):** QHHT/quantum healing, Psych-K, 9D breathwork, couples yoga, sound bath/gong bath, chakra cleansing, chi nei tsang, inner-child healing, mind-shifting, food-intolerance consultation, shamanic drum and Our Vibe. Each page needs a human title, outcome-led intro, provider proof, reviews, price, duration, next availability, online/local toggle and one primary booking CTA.

**P1 (high-intent hubs):** `/therapies/9d-breathwork`, `/therapies/sound-bath`, `/therapies/gong-bath`, `/therapies/qhht`, `/therapies/chi-nei-tsang`, `/therapies/reiki`, `/therapies/couples-yoga`, `/therapies/inner-child-healing`, `/therapies/silent-counselling`, `/therapies/mindfulness`, `/therapies/hypnotherapy`, plus matching `/online/...` hubs where inventory exists.

**P1 location pages:** London, Kent, Maidstone, Tunbridge Wells, Sevenoaks, Chislehurst, Orpington, Richmond, West Malling, South Shields and other locations already producing impressions or providers. Include a live provider count and suppress pages below the supply threshold.

**P1 decision pages:** “9D breathwork cost”, “distance Reiki prices UK”, “Psych-K training online”, “sound bath near me”, “QHHT near me”, “what is a wellness event”, “online versus in-person Reiki”, and modality comparison/first-session guides.

**P2 retention and commerce:** ethical “because you viewed/saved” collections, gift pages with real stock/availability, practitioner trust pages, repeat-session packages and event calendars. Keep books/products visually and technically separate from services.

## UX/UI conversion changes

- On the dominant UK mobile segment, put price, online/in-person, location, next available date and “Book” above the fold; retain two-line descriptions where they aid choice, but never hide the only availability cue on mobile.
- Use separate cards for product, offering, event and practitioner. A book must not look like a therapy appointment; each card should route to its canonical URL.
- Search results should load asynchronously with an explicit loading state, then replace pending cards, preserve query/filter state and report no-result/fallback events.
- Add “online alternative” only when a real online offer exists. For local searches, show distance and availability before marketing copy.
- Add trust near the CTA: practitioner identity, review count (or “new—no reviews yet”), credentials, cancellation policy, duration and what happens next.
- Personalisation should re-order sections from consented signals (recent query, viewed modality, saved item, dwell threshold, booking location) with a cold-start exploration mix and an explainable “Based on what you viewed” label. Never use hidden scarcity or dark patterns.

## 30/60/90-day plan and success metrics

**Days 0–30:** fix purchase instrumentation and duplicate tags; reconcile orders; remove null sitemap URLs; make slow sitemap endpoints bounded and observable; validate canonical/JSON-LD on top 20 pages; repair encoded slug redirects; instrument availability/no-results; improve P0 titles and above-fold booking proof.

**Days 31–60:** publish P1 modality and supply-backed location hubs; split product/event/offering templates; add UK mobile conversion experiments; submit one canonical sitemap index; build Search Console opportunity dashboard by query/page/location/device.

**Days 61–90:** add ethical behavioural ranking and online fallback; expand only winning supply-backed clusters; create cost/near-me guides; run provider acquisition against proven demand gaps; prune pages with impressions but no supply or engagement.

Track: transaction rate by channel/device/intent, `purchase` parity with orders, revenue per session, search CTR and position for opportunity pages, availability-to-book rate, no-result rate, mobile CTA-to-checkout rate, sitemap error/timeout count, indexed canonical percentage and provider coverage per demand cluster. The first target is trustworthy measurement; then move UK organic sessions from 0.10% observed transaction rate toward a measured baseline by page/intent rather than chasing raw traffic.

## Limitations

GA4 and Search Console were queried through the connected accounts on 15 September 2026. Search Console’s API retention means the reported GSC window is not a complete two years. GA4 “conversions” were intentionally treated as untrusted until event definitions are audited. Revenue can be incomplete if legacy Shopify, Stripe or consented users use a different property. No conclusion here proves causation; the proposed supply gaps must be checked against provider inventory and booking availability before new indexable pages are generated.
