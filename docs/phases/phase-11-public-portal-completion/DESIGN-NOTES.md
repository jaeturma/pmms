# Phase 11 Design Notes

- **Arena's own template has no Gallery/About/FAQ/Search/404 page to
  copy.** Re-confirmed by direct `WebFetch` 2026-07-29: the whole
  reference is one page, every nav item an in-page anchor. Nothing in
  this phase clones a literal Arena page for these five — each is built
  from Arena's general design language (card grids at a consistent
  aspect ratio, generous grid rhythm, structured section headers,
  three-column footer already built in Phase 10) applied to real PMMS
  content, the same "language not literal markup" discipline every
  phase since Phase 8.5 has followed.
- **Gallery is static, and "static" means non-photographic.** PMMS has
  no photo/media pipeline (confirmed again — no `Photo` model, no
  upload path for meet imagery anywhere in the schema) and stock/
  fabricated event photography was already ruled out once, in Phase
  10's hero-treatment decision. A gallery of *fake* photos claiming to
  depict real meet moments would misrepresent DepEd/PMMS content, so
  this phase's "static gallery" is a card grid of **sport-identity
  tiles** (icon/initial treatment, the same decorative approach
  `TeamLogo`/`sports-medal-strip.tsx`'s `sportIcon()` already use for
  sports with no real image asset) grouped by the meet's actual
  contested sports (`Meet::events()`, the same relation WP-10-07's
  Sports page already queries) — real data driving which tiles exist,
  no invented photography.
- **Rankings reuses Medal Tally's exact data, not a new computation.**
  `MedalTallyService::standings()` already powers `tally.tsx`'s
  "Overall ranking" table; the new `/rankings` route/page renders that
  same service output as its own destination, matching how WP-08.5-07's
  kiosk mode reused an existing route's data rather than adding a
  parallel path.
- **About uses only what `Division`/`Meet` actually store.**
  `Division::current()` gives `name` + `type` (`DivisionType`) +
  `areaLabel()`; combined with the active meet's own summary
  (`meetSummary()`, reused exactly, matching Phase 10's Contact
  precedent) plus a small set of real aggregate counts (competing
  municipalities, schools, sports contested — all already-queried
  elsewhere, e.g. `home()`'s municipality count, WP-10-07's sports
  grouping). No address/phone/email field exists on `Division` and none
  is added, the same resolution Phase 10 reached for Contact.
- **FAQs answers must trace to real, queryable facts.** Generic
  "how do I register" copy is written prose (like every static page's
  headings/labels already are), but any answer stating a fact ("when
  does the meet start," "is this the official result") must read from
  real data (`meet.starts_at`/`ends_at`, the "validated results only"
  guarantee already documented in `docs/public-portal.md`) rather than
  hardcoding a value that could silently go stale.
- **Search only ever touches already-public fields.** Reuses the exact
  privacy boundary `docs/public-portal.md` already enforces on Results/
  Tally/Sports: searchable entities are schools, sports/events, and
  published announcements (by name/title) plus **validated** result
  placements (by athlete name or school — the same name+school+
  placement triple already public on `/results`), all scoped through
  `Meet::published()`. Never birthdates, LRN, contact info, protests,
  incidents, or anything from an unpublished meet — same boundary,
  applied to a new entry point instead of a new rule.
- **404 is a visual pass, not a functional rebuild.** WP-04-06 already
  fixed the real functional gap (unstyled Laravel 404, no way back into
  the portal) — `error.tsx` already renders `PublicLayout` + a
  "Back to portal home" link for guests. WP-11-07 only elevates its
  spacing/typography to match Phase 10/11's card-grid discipline.
- **No new dependency, anywhere, for any reason.** Confirmed again
  before writing this plan: no charting library, no Framer Motion, no
  search library (`Meet`-scoped `LIKE`/`where` queries across a handful
  of small tables need no full-text search engine at PMMS's real data
  volume) in `package.json`/`composer.json`. All stay that way.
- **New routes are additive, read-only, and minimal** — same rule
  Phase 10's WP-10-07 proved out for Sports/News/Contact. Rankings,
  About, and Search are this phase's backend-touching WPs (WP-11-02,
  WP-11-04, WP-11-06); Gallery, FAQs, and the 404 pass are frontend-only
  (Gallery/FAQs still need one thin route+controller action each to be
  reachable as a real Inertia page, but query nothing beyond the
  standard `Meet::published()` lookup every existing public page
  already does).
