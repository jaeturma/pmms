# Phase 12 — Lightweight Sport Mini Portals

**Status:** Planned 2026-07-29 — WP-12-01 (inspection + data-contract map)
done same day; WP-12-02 onward pending owner instruction. This
directory was originally created as "Phase 08.6" by an external brief
that appeared in the repo unexplained (see `.ai/current-phase.md`'s
2026-07-29 entry); the owner explicitly instructed implementing it and
renumbering it to fit this project's real sequence (Phase 11 was the
most recently shipped phase). The original brief is kept verbatim,
just renamed, as `PHASE-12-LIGHTWEIGHT-SPORT-MINI-PORTALS-original-brief.md`.

## Goal

Build lightweight, permanent-URL sport pages (`/basketball`,
`/volleyball`, etc. — 12 sports total) for the public portal, each
showing the currently active meet's Live Now / Today's / Completed /
Upcoming games, Venue Information, Standings, Leading Scorers, and
Tournament Bracket for that one sport — fast, focused, low-bandwidth,
reusing one shared component system rather than 12 duplicated pages.
Frontend-focused: the existing backend (business logic, scoring,
medal computation, eligibility, auth, admin theme) stays untouched
except for small, additive, read-only routes/controller actions, the
same discipline every phase since Phase 10 has used for new public
pages.

## Scoped decisions (owner, 2026-07-29, resolved via `AskUserQuestion`
before any code — same process every large brief in this project has
gotten)

- **Standings, Leading Scorers, and a real Tournament Bracket diagram
  have zero backing data anywhere in this schema, for any sport**
  (`DATA-CONTRACT-MAP.md` §D/E/F) — no team win/loss aggregation, no
  per-athlete point attribution in live scoring, no bracket-tree
  structure exists today. Per the brief's own "do not fabricate data"
  rule: **honest "not available yet" states for all three, for every
  sport, this phase.** No new backend work. Same resolution WP-08-11
  already used for Athletics live-tracking.
- **Routing**: `/{sportSlug}` (no `/live` prefix, no meet ID) resolves
  via `Meet::published()->active()->first()` — the existing
  single-active-meet concept `home()` already uses. A real deviation
  from every other public route's `/meets/{meet}/...` pattern, but
  additive and structurally sound, not a conflict.
- **Rollout order**: basketball first (the brief's own Step 4 pilot,
  validated end-to-end), then generalize to the other 11 sports (Step
  5) — not all 12 built simultaneously.
- **Phase renumbered** from the brief's own "08.6" to **12**, to avoid
  colliding with the real, already-shipped Phase 8.5.

## Grounding

- All 12 named sports (Basketball, Volleyball, Baseball, Softball,
  Football, Sepak Takraw, Badminton, Table Tennis, Chess, Boxing,
  Athletics, Swimming) exist as real `Sport` catalog rows today
  (`SportsCatalogSeeder`/`DdopaaReferenceSeeder`) — confirmed before
  planning, not assumed.
- `live-score-display.tsx` already renders the complete live board
  (score, clock, `LiveBadge`, Basketball fouls, Boxing rounds,
  Softball/Baseball innings, fullscreen mode) — reused as-is for "Live
  Now," not rebuilt.
- `ScoreboardType` has dedicated boards for Basketball/Boxing/
  Softball-Baseball only; every other sport in this phase's scope uses
  the existing Generic side-score board — an established, deliberate
  fallback, not a gap.
- No new npm/composer dependency expected — no bracket-diagramming
  library, no charting library, matching every phase's own discipline
  since Phase 7.
- Every color/motion/spacing token from Phase 8.5/10/11 is reused
  verbatim; no new `@theme` color entry.

## Principles

- Frontend-focused; only small, additive, read-only backend routes/
  controller actions — no schema change, no business-logic change.
- Reuse Phase 8.5/10/11's existing design tokens and shared components
  (`LiveScoreDisplay`, `EmptyState`, `PublicPageHero`, `StatCard`,
  `TeamLogo`, `sportIcon()`) — build new shared components only where a
  real gap exists.
- One shared component system serving all 12 sports via configuration,
  never 12 duplicated pages.
- Honest empty/not-available states over fabricated data, per the
  brief's own explicit rule.
- One work package at a time; nothing committed or pushed without
  owner instruction.

## Work Packages

| WP | Title |
|---|---|
| WP-12-01 | Inspection Report and Data-Contract Map |
| WP-12-02 | Shared Sport-Portal Shell and Components |
| WP-12-03 | Basketball Reference Implementation |
| WP-12-04 | Generalize to the Remaining 11 Sports |
| WP-12-05 | Sport-Specific Exceptions (Athletics, Swimming, Boxing, Chess) |
| WP-12-06 | Performance and Visibility-Aware Polling |
| WP-12-07 | Accessibility and Loading/Error-State Review |
| WP-12-08 | SEO Metadata and Required Documentation |
| WP-12-09 | Testing and Phase Compliance Review |

Sequence follows the brief's own Step 1 through Step 8 order closely:
WP-12-02 (shared shell) must land before WP-12-03 (basketball, the
pilot); WP-12-03 must land before WP-12-04 (generalize); WP-12-05
(sport exceptions) can run any time after WP-12-04's config exists;
WP-12-06/07/08 can run in any order once WP-12-04 is done; WP-12-09
must be last.

## Visual Checkpoints

1. **After WP-12-03:** `/basketball` is a real, reachable, complete
   mini portal — Live Now, Today's/Completed/Upcoming Games, Venue
   Information all real; Standings/Leading Scorers/Bracket show honest
   "not available yet" states.
2. **After WP-12-04:** all 12 sport routes exist and render the same
   shared component system, each correctly adapted by its own
   `SportPortalConfig`.
3. **After WP-12-09:** full quality gate green, compliance review
   filed, Phase 12 closed.

## Exclusions (deferred or explicitly out of scope)

Any new schema/business logic for team win/loss standings, per-athlete
live-scoring point attribution, or a real bracket-tree data model (all
three: honest empty states only, this phase); any change to the
existing admin theme, business logic, scoring engine, medal
computation, eligibility, or authorization; any new charting/bracket-
diagramming/animation dependency; any change to `PublicBottomNav`'s
item count (this phase's 12 new pages are reachable via a new header-
nav treatment, not the existing meet-scoped nav — detail in
`DESIGN-NOTES.md`).

## Completion

Phase 12 completes via WP-12-09 (full quality gate + compliance
review), mirroring WP-11-09/WP-10-11. The review report goes to this
directory; the WP log lives in `.ai/current-phase.md`.
