# DdOPAA 2025 Reference Dataset

**Status:** Planned 2026-07-27 — pending owner approval. Execution has not
started. Not a roadmap "Phase" — a standalone, cross-cutting data
initiative, same category as the Division Type & Municipality-Based
Delegations initiative (7 WPs, completed 2026-07-25). Runs independently
of Phase 8 (Post-Deployment Support), which is unrelated in scope.

## Goal

Populate the development/demonstration environment with a realistic
dataset resembling the real 2025 Davao de Oro Provincial Athletic
Association (DdOPAA) meet — the real 11 municipalities, real sports, a
handful of real, corroborated facts about the actual event — layered with
clearly-labeled synthetic data for everything that can't be verified
(schools, athletes, matches, medal tallies, schedules). Seed data only;
never touches production, never a real athlete's name.

## Grounding — read before starting any WP

- **The Facebook page named as the primary source is inaccessible.**
  `WebFetch` against it returned only the page title, zero posts — no
  tool available in this environment can authenticate to or render
  Facebook. This was confirmed during planning, not assumed. Full source
  inventory: `docs/data-reference/ddopaa-2025-source-register.md`.
- **Owner decision 2026-07-27, given that gap:** proceed with a mostly-
  synthetic dataset. Use the small set of `PARTIALLY_VERIFIED` facts that
  *did* surface (via `WebSearch` snippets and Scribd document previews,
  not primary reads) as flavor — meet opened January 17, 2025 at
  Maragusan Grandstand Arena, all 11 municipalities participated, sports
  touched across sources (Athletics, Basketball incl. 3x3, Volleyball,
  Swimming, Gymnastics, Boxing), five real delegation nicknames
  (Nabunturan "Black Mamba," Montevista "Blazing Fighters," New Bataan
  "Rock Wreckers," Mawab "Pick Hammer," Maragusan "Maroon Knights"), and a
  handful of team-level (never individual-athlete-level) event outcomes.
  **Nothing beyond this short list is claimed as verified anywhere in
  this dataset** — no fabricated medal tally, no invented champion, no
  invented schedule presented as official.
- **One real student-athlete's name surfaced during research (a boxing
  gold medalist) and was deliberately not recorded anywhere, including
  in the source register.** Per the owner's own standing instruction, no
  real athlete name is used in this dataset without explicit
  authorization, regardless of whether it's already public.
- **Owner decision 2026-07-27: provenance is documentation-only, no
  schema changes.** No `source_type`/`reference_status`/`is_synthetic`
  columns on any business table — classification lives in seeder code
  comments and the two reference docs (WP7). Matches this project's
  existing "avoid unnecessary complexity" posture (`.ai/architecture.md`)
  and the request's own instruction to prefer the simplest compatible
  approach.
- **The existing schema needs no changes at all.** `District` (=
  municipality, the real 11 already seeded via `DivisionRegistrySeeder`),
  `School`, `Delegation`, `Athlete`/`Personnel`, `Sport`/`Event`, `Venue`,
  `EventSchedule`, `EventMatch`, `EventResult`/`ResultPlacement` (already
  encode→validate, medal tally derived at read time, never stored),
  `ScoringSession`/`ScoreEvent` (already provisional-only by construction)
  — every structure this dataset needs already exists and was built for
  exactly this shape of data across Phases 1–7 and the Division
  initiative.
- **Seeders stay flat, no subdirectories.** `database/seeders/*.php`,
  matching every existing seeder (`DivisionRegistrySeeder`,
  `SampleProvinceDemoSeeder`, `PerformanceBenchmarkSeeder`, etc.) — no
  `Reference/`/`Demo/`/`LoadTest/` folders.
- **Reuse before inventing:** `PerformanceBenchmarkSeeder` (WP-06-04)
  already serves the load-test tier at realistic scale (11 real
  municipalities, ~1,300 athletes) — it is not rebuilt, only optionally
  themed. `SampleProvinceDemoSeeder`'s existing small-scale pattern is the
  template for the demo tier, not a reason to add a third parallel
  system.

## Principles

- Every synthetic record is honestly synthetic — no result, score,
  schedule, or medal count is ever presented as if it came from the real
  2025 meet unless it's one of the handful of corroborated facts above.
- No real student-athlete names, ever, regardless of source.
- No PII beyond what the schema already collects for demo purposes
  (synthetic names, `@example.test`-style contacts where applicable, no
  medical/guardian/address data — the schema already can't hold most of
  this).
- Reuse existing models, policies, services (`MedalTallyService`, the
  encode→validate result flow, `ScoringSession`) exactly as built —
  this initiative adds data, not new application behavior.
- Non-destructive: no `migrate:fresh`, no deleting existing development
  records, `APP_ENV` guarded (`local`/`testing` only, matching every
  existing seeder's own convention).
- One work package at a time; nothing committed or pushed without owner
  instruction.

## Work Packages

| WP | Title |
|---|---|
| WP1 | Meet, Venue & Sports Catalog Setup |
| WP2 | Standard Dataset — Delegations, Schools, Athletes & Personnel |
| WP3 | Results & Medal Tally Reconciliation |
| WP4 | Live Scoring Samples (Basketball, Boxing, Softball/Baseball) |
| WP5 | Demo & Load-Test Tier Wiring |
| WP6 | Testing & Seeding Safety |
| WP7 | Documentation & Completion Review |

Sequence is mostly strict: WP2 needs WP1's meet/events; WP3 needs WP2's
delegations/athletes/entries; WP4 is independent of WP3 (live scoring
never touches results); WP5 can run any time after WP2; WP6 and WP7 close
the initiative last.

## Visual Checkpoints

1. **After WP1–WP2:** the Delegations, Schools, and Athletes pages show a
   realistic DdOPAA 2025-flavored roster across all 11 municipalities.
2. **After WP3:** the Medal Tally page shows a plausible, internally
   consistent standings table — clearly synthetic where the request
   itself couldn't be verified, never presented as the real 2025 result.
3. **After WP4:** the Schedule page's new "Live" column (added the
   session before this initiative) shows real scheduled/live/completed
   games across three sports, each with correct sport-specific state.
4. **After WP7:** full quality gate green, documentation complete,
   reconciliation tests passing, initiative closed.

## Exclusions (deferred or explicitly out of scope)

Any data claimed as `VERIFIED_OFFICIAL` beyond the short corroborated list
above; a complete or official DdOPAA 2025 medal tally or named champion;
real student-athlete names; real photos; provenance database columns/
tables; new hosting/cloud infrastructure; Docker/CI automation; Flutter;
AI. If real Facebook post content becomes available later (the owner
supplies it directly), that's a future, separately-scoped addition, not
retrofitted into this initiative's synthetic data silently.

## Completion

Closes via WP7 (full quality gate + completion review, following the same
convention as every phase's closing WP), logged in `.ai/current-phase.md`.
