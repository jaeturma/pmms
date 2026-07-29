# Phase 12 — Data-Contract Map

**Step 2 of the phase's own workflow** (`PHASE-12-LIGHTWEIGHT-SPORT-MINI-PORTALS-original-brief.md` §14). Per the brief's own instruction: *"Do not assume fields exist... Identify: existing fields, missing fields, fields requiring formatting only, fields unavailable requiring an empty state."* Every row below is verified against real source (`INSPECTION-REPORT.md`), not assumed.

## A. Routing / meet resolution

| Need | Source | Status |
|---|---|---|
| `/{sportSlug}` (e.g. `/basketball`), no `/live` prefix | New route, resolves `Sport::where('name', ...)` from a slug↔name map, then `Meet::published()->active()->first()` (same resolution `home()` already uses) | **Buildable**, additive-only, no schema change |
| "Not applicable" when no sport matches the slug, or no active meet exists | `EmptyState`, same convention as every existing empty portal state | **Buildable** |

## B. Live Now

| Field | Source | Status |
|---|---|---|
| Featured live match for this sport | `ScoringSession::where('status', '!=', Ended)->whereHas('match', meet+sport)` — same query shape `PortalController::liveMatches()` already runs, filtered further by sport | **Existing, formatting only** |
| Score, clock, sport-specific state (fouls/rounds/innings) | `ScoringSession::toLivePayload()` + `live-score-display.tsx` (already renders all of this) | **Existing, reusable component as-is** |
| Venue, tournament stage/category | `EventMatch->schedule->venue`, `round_label`, `Event->gender`/`age_division` | **Existing, formatting only** |
| "Other live events" count/selector | `ScoringSession::where(...)->count()` for the sport, minus the featured one | **Existing, formatting only** — brief itself makes this conditional on "if the existing backend supports it efficiently," which it does |
| No live event → next scheduled | `EventSchedule` next slot for the sport | **Existing, formatting only** |

## C. Today's / Completed / Upcoming Games

| Field | Source | Status |
|---|---|---|
| Scheduled time, competitors, venue, category, status | `EventMatch` + `EventSchedule` + `Event`, same shape `meet.tsx`/`athletics.tsx` already query | **Existing, formatting only** |
| Score when available (Today's Games) | `ScoringSession` if a session exists for that match | **Existing, formatting only** |
| Final score/winner (Completed Games) | `ScoringSession.score_a/score_b` — **"winner" is not a stored field**, must be derived client/server-side as `score_a <=> score_b` at render time | **Existing field, missing a stored "winner" — derivable, not a gap** |
| Max 10 items, ordered | Query `->limit(10)->orderBy(...)` | **Existing, formatting only** |

## D. Standings

| Field | Source | Status |
|---|---|---|
| Rank, team/municipality, Played/Won/Lost/Draw, points | **No source anywhere.** No W-L aggregation exists; `MedalTallyService` computes medal counts per municipality across an entire meet, not a per-sport league table derived from individual match outcomes | **Missing — real backend gap** (§9 of the Inspection Report) |

Per the brief's own §8 rule ("render... a clear 'Not applicable for this sport' state... do not fabricate data"): **render a permanent "Standings not available yet" state for every sport**, not built this pass.

## E. Leading Scorers

| Field | Source | Status |
|---|---|---|
| Rank, athlete, team, games played, total/average points | **No source anywhere.** Every `ScoreEvent` in this app is side-level (`score_a`/`score_b`); no column or event type attributes a point to an individual athlete, for any sport, ever | **Missing — real backend gap** |

Same resolution as Standings: **"Leading scorers not available yet"** for every sport.

## F. Tournament Bracket

| Field | Source | Status |
|---|---|---|
| Bracket tree / seeding / progression | **No source anywhere.** `matches.round_label` is a free-text string with no parent/child or seed column | **Missing — real backend gap** |
| Round-robin compact summary (the brief's own fallback, §8.7, "If the sport uses round-robin only...") | `EventMatch` grouped by its own `round_label` string, listed flat | **Partially buildable** — a flat "here are this sport's rounds and their matches" list is real and buildable; a genuine bracket *diagram* with progression lines is not, since nothing encodes which match feeds into which |

Resolution: render the round-robin-style flat compact summary (real data, the brief's own pre-authorized fallback for exactly this shape of tournament), not a bracket diagram.

## G. Venue Information

| Field | Source | Status |
|---|---|---|
| Venue name, municipality, address | `Venue.name`/`.address`; municipality via the venue's schedule slots' meet/delegation context, or simply the address text | **Existing, formatting only** |
| Current/next event at this venue | `EventSchedule` filtered by venue + sport | **Existing, formatting only** |
| Directions link | No stored geo field — build a `https://www.google.com/maps/search/?api=1&query=` link from `name`+`address` text | **Buildable, no new field** |

## H. Sport configuration (`SportPortalConfig`, brief §4)

| Field | Source | Status |
|---|---|---|
| `slug`, `name` | A small static slug↔`Sport.name` map (12 entries) — `Sport` rows themselves already exist for all 12 named sports, confirmed via `SportsCatalogSeeder`/`DdopaaReferenceSeeder` | **Buildable, no schema change** |
| `scoringType` | Derived from `ScoreboardType::forSport()` (already exists) plus a manual per-sport label map for the 9 sports without a dedicated board | **Buildable** |
| `supportsStandings`/`supportsLeadingScorers`/`supportsBracket` | Per §D/E/F above: **`false` for every sport**, today | **Buildable, honestly `false` everywhere** |

## Net result

Five of eight required sections (Live Now, Today's/Completed/Upcoming Games, Venue Information) are real and buildable from existing data with no backend change beyond new, additive, read-only routes/controller actions — the same discipline every prior public-portal WP in this project has used. Three (Standings, Leading Scorers, Tournament Bracket-as-a-diagram) have no real data anywhere in the schema for any sport and, per the brief's own explicit "do not fabricate data" rule, resolve to an honest not-yet-available state — not a partial or fake implementation.

## Owner decisions (confirmed 2026-07-29, before any code)

- **Standings/Leading Scorers/Bracket-as-diagram**: honest "not available yet" empty states now, for every sport; no new backend work this phase. Same resolution WP-08-11 already used for Athletics live-tracking.
- **Routing**: `/{sportSlug}` resolves via `Meet::published()->active()->first()` (the existing single-active-meet concept), confirmed over keeping a meet-scoped URL pattern.
- **Rollout order**: basketball first (the brief's own Step 4 pilot), then generalize to the remaining 11 sports (Step 5) — not all 12 at once.
