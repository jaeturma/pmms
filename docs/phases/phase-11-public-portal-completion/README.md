# Phase 11 — Public Portal Completion (Rankings, Gallery, About, FAQs, Search, 404)

**Status:** Planned 2026-07-29 — WP-11-01 done same day; WP-11-02 onward
pending owner instruction. This directory did not exist before this plan.
Scoped via owner Q&A the same day (`AskUserQuestion`, four decisions,
all resolved before drafting), the same approach every phase since
Phase 6 has used.

## Goal

Close the gap between the original Arena-inspired public-portal brief
and what **Phase 10 ("Premium Portal Redesign," 11 WPs, complete,
committed `791b06d`)** actually shipped. Phase 10 delivered the shell
rebuild (sticky nav, real footer), composition/spacing elevation across
Home/Schedule/Results/Live/Tally, and three new pages (Sports, News,
Contact) — but explicitly deferred Gallery and a separate Rankings
route, and never built About, FAQs, or Search. This phase builds those
remaining pages, using the same Arena-as-layout-inspiration discipline
Phase 10 established, and gives the guest-facing 404 page a matching
visual pass.

This is **UI + minimal additive-backend** work, same discipline as
Phase 10's WP-10-07 (Sports/News/Contact): a handful of new read-only
routes/controller actions reusing existing models and scopes, zero new
migrations, zero new business logic, zero change to authorization,
live scoring, medal computation, or any existing route/controller/page.

## Scoped decisions (owner, 2026-07-29)

- **This is a new phase for the gap**, not a reopening of Phase 10 and
  not a redo — Phase 10's shipped output stands as-is.
- **Gallery is a static, frontend-only page** — no `Photo`/media model,
  no migration, no upload. PMMS still has no photo pipeline and the
  brief's "no backend changes" instruction rules out adding one here.
  See DESIGN-NOTES for what "static" means concretely (not fabricated
  event photos).
- **Rankings gets its own `/meets/{meet}/rankings` route**, reversing
  Phase 10's "fold into Medal Tally" decision now that the owner wants
  it split out — reuses `MedalTallyService::standings()` exactly
  (same data Medal Tally's own "Overall ranking" table already renders),
  no new computation.
- **About, FAQs, and Search are all in scope**, built from real,
  already-existing PMMS data only — same "no invented content"
  discipline Phase 10's Contact page used.

## Grounding

- **The Arena reference has no separate Gallery/About/FAQ/Search/404
  pages to reverse-engineer.** Re-fetched directly (`WebFetch`,
  2026-07-29, not assumed from Phase 10's notes) and confirmed: it is a
  single-page template — every nav item (`Fixtures`, `Results`, `Squad`,
  `News`, `Membership`, `Buy Tickets`) is an in-page anchor (`#section`),
  not a separate URL. Phase 10's own WP-10-01 mapping already recorded
  this template as one hero + sticky nav + card grids + three-column
  footer, nothing page-type-specific beyond that. This phase therefore
  applies Arena's general design *language* (card grids, generous grid
  rhythm, consistent card aspect ratios, structured section headers) to
  each new page, rather than cloning a literal Arena "gallery page" or
  "FAQ page" that doesn't exist in the reference. WP-11-01 records this
  finding formally so no later WP goes looking for a reference page that
  isn't there.
- Every color/status/medal/motion token from Phase 8/8.5/10 is reused
  verbatim; no new `@theme` color entry.
- No new npm or composer dependency expected — matches every phase's
  discipline since Phase 7 (broken only for Reverb, a real technical
  need).
- `Division` (`app/Models/Division.php`) has `name` + `type`
  (`DivisionType` enum) + `areaLabel()` — real content for About, no
  address/phone/email field exists (same gap Phase 10 hit for Contact,
  same resolution: don't invent one).
- The public-portal privacy baseline (`docs/public-portal.md`) binds
  Search exactly like every existing public route: only already-public
  fields (meet/school/sport/event names, published announcements,
  validated results' athlete name+school+placement) are searchable —
  never birthdates, LRN, contact info, or anything from an unpublished
  meet.
- `resources/js/pages/error.tsx` already handles guest 404s correctly
  (WP-04-06: `PublicLayout` + "Back to portal home" link) — WP-11-07 is
  a visual-composition pass only, not a functional rebuild.

## Principles

- Layout/spacing/composition inspiration only — never Arena's actual
  HTML, CSS, colors, or branding, and never a Bootstrap conversion.
- Reuse Phase 8/8.5/10's existing design tokens and shared components;
  extend only when a real gap exists.
- New routes are additive and read-only, scoped through
  `Meet::published()` exactly like every existing public route.
- One work package at a time; nothing committed or pushed without
  owner instruction.

## Work Packages

| WP | Title |
|---|---|
| WP-11-01 | Arena Gap Audit, Design Analysis, and Migration Plan |
| WP-11-02 | Rankings Page (Split from Medal Tally) |
| WP-11-03 | Static Gallery Page |
| WP-11-04 | About Page |
| WP-11-05 | FAQs Page |
| WP-11-06 | Public Portal Search |
| WP-11-07 | 404 Page Visual Elevation |
| WP-11-08 | Navigation and Footer Integration for New Pages |
| WP-11-09 | Accessibility, Responsive Review, and Phase Compliance Review |

Sequence: WP-11-02 through WP-11-07 don't depend on each other and may
run in any order. WP-11-08 must come after all six new/changed pages
exist (it wires them into the header nav and footer's quick-links
column in one pass, same rationale as Phase 10's shared-nav-array
reuse). WP-11-09 must be last.

## Visual Checkpoints

1. **After WP-11-02/03/04/05/06:** `/meets/{meet}/rankings`,
   `/meets/{meet}/gallery`, `/meets/{meet}/about`, `/meets/{meet}/faqs`,
   and `/meets/{meet}/search` are real, reachable pages built with the
   phase's card-grid/spacing discipline.
2. **After WP-11-08:** every new page is reachable from the header nav
   and footer quick-links — `PublicBottomNav` unchanged (still 4–5 items,
   the Phase 8.5-05/Phase 10 one-thumb-reach decision).
3. **After WP-11-09:** full quality gate green, compliance review filed,
   Phase 11 closed.

## Exclusions (deferred or explicitly out of scope)

Any `Photo`/media model or upload capability (Gallery stays static);
any office-contact field (About/FAQs/Search show only real, already-
public data); any change to `PublicBottomNav`'s item count; any new
charting library, Framer Motion, or other new dependency; any change to
color tokens, business logic, database schema beyond the additive
read-only routes each WP describes, authorization, or the result-
integrity/medal-computation core; re-touching any Phase 10 page beyond
what WP-11-08's nav/footer wiring requires.

## Completion

Phase 11 completes via WP-11-09 (full quality gate + compliance
review), mirroring WP-10-11/WP-08.5-10/WP-09-03. The review report goes
to this directory; the WP log lives in `.ai/current-phase.md`.
