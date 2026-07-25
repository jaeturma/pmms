# Phase 4 Checklist

- [x] WP-04-01 — Public Portal Shell and Navigation
- [x] WP-04-02 — Public Meet Overview
- [x] WP-04-03 — Public Schedule and Venue Guide
- [x] WP-04-04 — Public Results
- [x] WP-04-05 — Public Medal Tally and Rankings
- [ ] WP-04-06 — Public Delegation and School Directory — **not built, by design**
- [ ] WP-04-07 — Public Athlete and Team Profiles — **not built, by design**
- [ ] WP-04-08 — Announcements and Downloads — **partial**
- [ ] WP-04-09 — Public Search and Filters — **partial**
- [x] WP-04-10 — Public Portal Accessibility and Mobile Review
- [x] WP-04-11 — Phase 4 Review and Acceptance

Verified 2026-07-24 against live code, docs, and the 545/545 Pest suite — see
the status note in README.md. Unlike Phase 3's recheck, this plan is not a
clean relabeling of finished work: two items describe features the real
product scope explicitly excludes, two are half-done, and one was genuinely
the next real task. WP-04-06/07 exclusion and WP-04-08/09 partial scope both
reconfirmed by explicit owner decision 2026-07-25 (not just this doc's
inference). WP-04-11 executed 2026-07-25 —
`phase-4-compliance-review.md`, COMPLIANT.

## Item-by-item

- **WP-04-01/02/03/04/05, WP-04-10** — match what's built (portal shell +
  layout, meet header/overview, schedule & venue guide, results, medal tally,
  and today's accessibility/mobile review). Every one of these new WP files
  also states "Keep municipality as the official delegation" in its Core
  Rules — that's stale and factually wrong (delegations are per-**school**,
  see `docs/delegations.md`); it doesn't describe an actual deliverable
  specific to these WPs, so it didn't block checking them off, but it
  shouldn't be treated as accurate going forward.
- **WP-04-06 Public Delegation and School Directory** — not built. Product
  scope (`docs/00-product/product-scope.md` §9, cited in the real
  `WP-04-01` doc before it was overwritten) puts a public delegation/school
  directory **out of scope**. This isn't a missed deliverable; building it
  would be a scope violation.
- **WP-04-07 Public Athlete and Team Profiles** — not built. Product scope
  explicitly **defers** public athlete/team profile pages — minors' data,
  deliberately kept off the public portal beyond name+school+placement on
  results. Same as above: not missing, excluded on purpose.
- **WP-04-08 Announcements and Downloads** — Announcements half is done
  (`PublicAnnouncements`, `docs/announcements.md`). "Downloads" (public
  file/report export) doesn't exist — the portal is read-only pages; CSV/print
  exports are internal-only (`docs/reports.md`). Consistent with product
  scope deferring a public API/export surface, but genuinely not built.
- **WP-04-09 Public Search and Filters** — Filters half is done (sport filter
  on results/tally, shared `validatedSportOptions()`). General "public
  search" across meets/schools/entities doesn't exist and is explicitly
  listed as out of scope in the real Phase 4 README's exclusions.
- **WP-04-11 Phase 4 Review and Acceptance** — done 2026-07-25. Same substance
  as the real plan's WP-04-07 (Phase 4 Compliance Review); executed against
  this project's real conventions rather than this WP file's generic
  boilerplate. Result: COMPLIANT — see `phase-4-compliance-review.md`.
