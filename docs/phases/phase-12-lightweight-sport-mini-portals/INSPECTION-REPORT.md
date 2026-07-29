# Phase 12 — Inspection Report

**Step 1 of the phase's own workflow** (`PHASE-12-LIGHTWEIGHT-SPORT-MINI-PORTALS-original-brief.md` §14). Read in full first. Findings below are grounded in the actual current source (models, migrations, controllers, components) — not assumed from the brief's own examples.

## 0. A naming/numbering note, flagged before anything else

This repo's real phase sequence was at **Phase 11** (`docs/phases/phase-11-public-portal-completion/`, complete, committed, pushed as `8f0e41f`) when this brief arrived. The brief itself calls itself "Phase 08.6," a slot that doesn't fit the existing sequence (Phase 8, 8.5, and 8.6-as-originally-named would collide). Owner decision: renumbered to **Phase 12** to avoid confusion with the real Phase 8.5 (Premium Sports Experience, already shipped) — this directory and every doc in it use that numbering; the original brief file is kept verbatim (just renamed) as the source reference.

## 1. Public routes (current)

All under `routes/web.php`'s `throttle:60,1` guest group, **every one meet-scoped** (`/meets/{meet}/...`), resolved via `Meet::published()`:

`/` (home, the single active+published meet), `/meets/{meet}`, `/meets/{meet}/results`, `/meets/{meet}/tally`, `/meets/{meet}/rankings`, `/meets/{meet}/athletics`, `/meets/{meet}/sports`, `/meets/{meet}/gallery`, `/meets/{meet}/news`, `/meets/{meet}/contact`, `/meets/{meet}/about`, `/meets/{meet}/faqs`, `/meets/{meet}/search`, `/meets/{meet}/matches/{match}/scoreboard(+/poll)`.

**No route in this app is a bare top-level slug** (`/basketball`) — the brief's required pattern (§13, "Required pattern: `/{sportSlug}`, do not create `/live/basketball`") is a genuine deviation from every existing convention here. It's resolvable without new business logic: `Meet::scopeActive()` already guarantees exactly one active meet system-wide (enforced by `MeetController::activate()`, auto-exclusive), and `home()` already resolves `Meet::published()->active()->first()` the same way a `/basketball` route would need to. So `/basketball` → "the active meet's Basketball content" is a legitimate, additive new route, not a structural conflict — just a pattern this app has never used before (see Data-Contract Map §A).

## 2. Existing public pages (`resources/js/pages/public/`)

`home.tsx`, `meet.tsx` (day-selector schedule + venue guide), `results.tsx` (validated-only, sport-filterable), `tally.tsx` (medal standings, sport/age-filterable, kiosk mode), `rankings.tsx`, `athletics.tsx` (a deliberate "real shell, no live per-athlete data" precedent — see §6), `sports.tsx`, `gallery.tsx`, `news.tsx`, `contact.tsx`, `about.tsx`, `faqs.tsx`, `search.tsx`, `scoreboard.tsx` (one match's live board).

## 3. Reusable Arena-cloned components

Directly reusable for a sport mini-portal, no rebuild needed:

- `public-page-hero.tsx` — branded title/description/meta band (h1).
- `live-score-display.tsx` — the **full live scoreboard** already: score, running clock, `LiveBadge`, fullscreen mode, Basketball fouls, Boxing round history, Softball/Baseball inning-by-inning — exactly what a "Live Now" section needs, already meet+match-agnostic (takes a session payload as a prop).
- `team-logo.tsx` — deterministic colored-initials badge (no photo pipeline anywhere in this app — see §7).
- `sports-medal-strip.tsx`'s exported `sportIcon(name)` — per-sport decorative icon matcher, already used by `sports.tsx`/`gallery.tsx`.
- `rank-badge.tsx`, `medal-table-parts.tsx` (`MedalHeader`/`MedalCells`), `stat-card.tsx`, `empty-state.tsx`, `public-loading-skeleton.tsx`, `public-announcements.tsx`, `public-footer.tsx`, `public-bottom-nav.tsx`, `public-meet-nav.tsx`.
- `resources/js/hooks/use-kiosk-mode.ts` — existing `?kiosk=1` pattern (`tally.tsx`, `scoreboard.tsx`) already proves a large-display/auto-refresh mode without a new route.

## 4. Inertia props / APIs (`PortalController.php`)

Every public method builds its own minimal, purpose-built prop array (never reuses an internal/admin page's props) — the binding rule `docs/public-portal.md` states and every WP since Phase 4 has followed. Two private helpers already exist and are reusable for a sport portal: `contestedSports(Meet $meet)` (real sports contested in a meet, with event counts) and `participatingSchoolIds(Meet $meet)` (real schools with athletes registered in a meet).

## 5. Live scoring data model (`EventMatch`/`ScoringSession`/`ScoreEvent`)

- `matches` table: `meet_id`, `event_id`, `event_schedule_id`, **`round_label` — a free-text string** (e.g. "Quarterfinal"), `sequence`, `status` (`MatchStatus`: Scheduled/Completed/Walkover/Cancelled). **No parent/child match linkage, no seed number, no bracket-tree column of any kind.**
- `scoring_sessions`: `side_a_label`/`side_b_label` (free text — school/team names or manual entry), `score_a`/`score_b`, `period_label`. **Side-level only — no per-athlete attribution anywhere in this table.**
- `score_events`: `type` (`ScoreEventType`: Point, Correction, PeriodChange, Ended, Foul, RoundScore, Count, InningRun) + a JSON `payload`. Read the actual `ScoringSessionController` call sites for every type — **every single one is a side-level event** (`side: 'a'|'b'`-style payloads for basketball/boxing/softball). No event type anywhere attributes a point to an individual athlete.
- `ScoreboardType` (dedicated live-board UI): **Basketball, Boxing, Softball/Baseball only.** Every other sport this brief names (Volleyball, Football, Sepak Takraw, Badminton, Table Tennis, Chess, Athletics, Swimming) falls back to the Generic side-score board — an existing, working, deliberate fallback (`docs/live-scoring.md`), not a gap to close.

## 6. Individual-athlete results (`EventResult`/`ResultPlacement`)

`EventResult` belongs to `event` (the sport/discipline+gender+age-division catalog entry) and `meet` — **it has no `match_id` column at all.** Team-sport match outcomes (`ScoringSession`'s final `score_a`/`score_b`) and official medal placements (`EventResult`/`ResultPlacement`, entered by an organizer through the separate encode→validate workflow) are **two entirely disconnected systems** in this app. A match ending 78–65 does not automatically produce or update any result/placement row — an organizer has to separately encode who placed 1st/2nd/3rd in that event, and only *validated* placements ever count toward the medal tally.

This is the exact same real gap `WP-08-11` (Athletics live-tracking) already hit and resolved: `public/athletics.tsx` is a deliberate "real shell, no live per-athlete data" page, with an explicit banner stating the limitation, built after the owner was presented three options (real shell / new backend infra / defer) and chose the shell. That precedent is directly relevant here (§ Data-Contract Map, Standings/Leading Scorers/Bracket rows).

## 7. Venue data

`Venue`: `name`, `address`, `notes`, `active` — no geo-coordinates, no embedded-map-ready field. "Directions link" (brief §8.8) is achievable as a generic external map-search URL built from `name`+`address` text — consistent with the brief's own "no heavy embedded map" rule — not a stored field.

## 8. Design tokens / motion / admin-public boundary

Nothing new needed here: `--duration-base`/`--ease-premium`, `LiveBadge`'s pulse, `animate-card-in`, the global `prefers-reduced-motion` reset, and the admin-vs-public structural separation (no public page imports `ui/sidebar.tsx`; no admin page uses `PublicPageHero`/`.bg-premium-hero`) are all pre-existing and unaffected by anything in this brief. `docs/ui-ux/premium-design-system.md` and `docs/public-portal.md` are the two documents to extend, matching every prior public-portal WP's convention.

## 9. Summary — what's real vs. what would need new backend work

| Brief section | Backing data today | Verdict |
|---|---|---|
| Live Now | `ScoringSession`/`live-score-display.tsx` (full board, reusable as-is) | **Real**, reusable directly |
| Today's/Completed/Upcoming Games | `EventMatch` + `EventSchedule` (date/venue), same shape `meet.tsx`/`athletics.tsx` already use | **Real**, straightforward to build |
| Venue Information | `Venue` (name/address); directions via a generated map-search link | **Real**, straightforward |
| Standings (W-L records) | **None** — no team/side win-loss aggregation exists anywhere; medal tally is a completely different concept (municipality/school medal counts, not a per-sport league table) | **Not real** — would require new aggregation logic against data (a stored "winner" per match) that doesn't exist either |
| Leading Scorers (top 5 athletes by points) | **None** — every scoring event in this app is side-level (`score_a`/`score_b`); no event type or column attributes a point to an individual athlete anywhere in the schema | **Not real** — would require new columns/business logic (individual-athlete point attribution during live scoring), squarely business-logic/schema work the brief's own §1 says to preserve, not modify |
| Tournament Bracket | **None** — `round_label` is a free-text string with no parent/child/seed structure | **Not real** — would require a new bracket-tree data model |

Three of the brief's eight "required" sections (§2) have **zero backing data anywhere in this schema**, for every sport including the brief's own chosen pilot (basketball). The brief's own rules already anticipate this exact situation and pre-authorize the resolution: §4 lets a sport's config mark a section unsupported; §8 (per-section requirements) says *"If a section is not applicable to a sport, render a sport-appropriate compact alternative or a clear 'Not applicable for this sport' state. Do not fabricate data."* Applying that rule as written, Standings/Leading Scorers/Bracket resolve to an honest, permanent "Not available yet" state for every sport in this system today — the same resolution this codebase already chose once before, for the same underlying reason, in WP-08-11's Athletics page.

See `DATA-CONTRACT-MAP.md` for the full per-section field mapping.
