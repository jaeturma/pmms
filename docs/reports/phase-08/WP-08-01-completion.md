# WP-08-01 — Screenshot and Current UI Gap Assessment

**Status:** Complete 2026-07-27. Assessment only — no application code
changed. Per this WP's own rules, WP-08-02 has not been started.

## Method note — read before the findings below

The plan calls for screenshots of the current UI. The Claude in Chrome
browser extension was not connected for this session
(`tabs_context_mcp` returned "Browser extension is not connected" on
two attempts, before and after the rest of this assessment). Rather
than block on that, this assessment is built by reading the actual
source of every page a reference image maps to — the React/TSX
components, their Tailwind classes, the CSS custom properties that
drive the theme, and the backend data each page actually receives.
This is a legitimate substitute for a gap assessment (it captures
*structural* gaps — missing sections, missing data, missing
functionality — at least as precisely as a screenshot would, and more
precisely for anything a screenshot can't show, like whether a color
system exists at all), but it is not the same as a pixel-by-pixel
visual comparison. **Recommend re-running a visual pass with real
screenshots once the extension is reconnected**, before WP-08-13's
shared-component work and WP-08-15's visual regression review — those
two specifically benefit from actual rendered screenshots in a way
this WP's structural comparison can't substitute for.

## Repository findings

Read before writing this report: `.ai/project-rules.md`,
`.ai/current-phase.md`, `.ai/work-package-runner.md`,
`.ai/ui-ux-rules.md`, this WP's own doc, and all 5 reference images.

The reference images use a mix of real Davao de Oro municipality names
(Mabini, Compostela, Laak, Maco, Mawab, Montevista, Nabunturan) and
fictional ones not in the real 11 (New Corella, Asuncion, Maranan) —
confirming they are **visual references only**, exactly as their own
`docs/ui-ux/references/README.md` says ("Design references only; never
use as backgrounds"). No value from them should ever appear as real
data in the app; every later WP must pull from the actual `Meet`/
`Delegation`/`District`/`ResultPlacement` models, same as today.

## The one root cause behind almost every visual gap

`resources/css/app.css`'s entire theme is `oklch(x 0 0)` — **zero
chroma, on every single token** (`--primary`, `--sidebar`,
`--background`, both light and dark mode). This is shadcn/ui's
unmodified default grayscale starter theme. There is currently no
PMMS brand color anywhere in the app — no navy, no gold, no colored
icon badges, no colored banners. Nearly every visual difference
documented below traces back to this one fact, which is exactly what
WP-08-02 (PMMS Design Tokens and Visual Standards) is scoped to fix
next. This assessment treats "no color system" as a single root-cause
finding rather than repeating it under every section.

## Per-reference gap assessment

### 1. `admin-dashboard.png` → `resources/js/pages/dashboard.tsx`

| Reference shows | Current implementation | Gap |
|---|---|---|
| Dark navy sidebar, gradient torch logo, 15+ grouped nav items | `AppSidebar` (shadcn `Sidebar`, `variant="inset"`), same 16 items but flat, no section grouping beyond role-based show/hide, default light/gray theme | Visual only — nav item set is already close; needs grouping + the color system |
| Header: date/time, notification bell with count badge, avatar+role | `AppSidebarHeader` — just a sidebar toggle and breadcrumbs | Functional gap: no notification system exists yet to back a bell icon. Needs a decision in WP-08-03 on whether to build one or omit it, not just restyle |
| 5 colorful circular icon-badge stat cards | `StatCard` — plain white card, small monochrome muted-icon, no badge/circle | Visual only |
| Medal Tally (Top 5) with rank medals emoji-style icons | `dashboard.tsx`'s `MeetOperations` "Medal tally — top five" table — plain table, no medal icons | Visual only, data already present (`tallyTop`) |
| Donut chart "Events Overview" (Completed/Ongoing/Upcoming/Cancelled) | No chart. `Meet.status` is a single enum, not a per-event breakdown of this shape | **Functional gap** — the current data model doesn't track individual event status this way; needs backend design, not just a chart component |
| Registration Status donut (Completed/Pending/Declined) | Not present in any form | Functional gap — no such aggregate exists today |
| Quick Actions button grid | Not present (actions are per-page, not centralized on the dashboard) | Visual/UX only, no new backend needed — every linked action already exists as its own page |
| Today's Schedule with ONGOING/UPCOMING badges | `MeetOperations`'s "Today's schedule" table exists with real data, no status badge | Visual only |
| Announcements panel | Dashboard has no announcements section; `PublicAnnouncements` component exists and is used on the public side only | Visual/composition — reusable component exists, just not wired into the admin dashboard |
| Recent Activities with colored icon per action type | `recentActivity` table exists, plain rows, no icon | Visual only |

### 2. `admin-medal-tally.png` → `resources/js/pages/tally/index.tsx`

| Reference shows | Current implementation | Gap |
|---|---|---|
| Filter bar: Ranking By / As of Date / Sport / Division + Filter button | Two `Select`s only (meet, sport) | Visual/UX — "as of date" and "ranking by school vs municipality" toggle don't exist; municipality-vs-school is already two separate tables today (an existing, deliberate design per `docs/medal-tally.md`), so this is a UX reconciliation decision for WP-08-05, not a pure restyle |
| 4 colored summary cards (Total Gold/Silver/Bronze/Medals) + "+N from yesterday" | None — page goes straight into the standings table | Visual + a "since yesterday" delta requires either a stored snapshot or a computed diff against yesterday's validated results — **functional gap**, needs a design decision |
| Donut "Medal Distribution" + "Top Municipalities by Points" + "Medals by Sport" side panels | None of these three exist | Functional gap for the points system specifically — `MedalTallyService` computes gold/silver/bronze/total only, no weighted "points" (Gold=3/Silver=2/Bronze=1) field. The per-sport breakdown is derivable from existing data (`sport_id` filter already exists) but isn't rendered this way today |
| Rank medal icons (🥇🥈🥉) + municipality seal/logo per row | Plain rank number, plain municipality name text | Visual only — no municipality logo/seal image exists in the schema; would need a new nullable image field or a static asset map if pursued |
| "Ranking is based on: Gold (3) · Silver (2) · Bronze (1)" points note | `Tally.tsx` shows "Ties share medals; corrections to validated results update the tally automatically" — different, correct-for-today message | Confirms points-based ranking is new territory, not an existing computation |

### 3. `athlete-eligibility-checker.png` → `resources/js/pages/eligibility/index.tsx`

**This is the largest functional gap found in this assessment, not a visual one.**

| Reference shows | Current implementation | Gap |
|---|---|---|
| Search-and-check flow: pick athlete + sport + event + category, click "Check Eligibility", get an instant PASS/FAIL breakdown across 7 numbered rules (age, grade level, residency, school enrollment, documents, event-entry limit, duplicate-entry check) with a final ELIGIBLE/INELIGIBLE verdict | A **document upload + manual human review queue** — `EligibilityReview` records with `pending`/`approved`/`returned` status, decided by an admin/organizer clicking Approve or Return with remarks. There is no automated rule engine anywhere in the codebase | **This is a different feature, not a re-skin.** The current design is deliberate — `EligibilityReview`'s own doc comment says decisions are "always made by a person," matching this WP's own required-reading rule that authorization/validation logic must be preserved. WP-08-06 needs a scoping decision from the owner: (a) build the automated checker as a genuinely new, additive feature (age/grade/residency/duplicate-entry checks are all derivable from existing `Athlete`/`School`/`Entry` data and would be real, useful, non-cosmetic additions), while keeping the existing document-upload/manual-approval workflow intact for the parts that must stay human-decided (document completeness, final say); or (b) treat the reference purely as a *display* pattern to restyle the existing manual-review list around, without adding new rule automation. This is not WP-08-01's call to make — flagging it for WP-08-06 specifically. |

### 4. `desktop-basketball-live-score.png` / `mobile-basketball-live-score.png` → `resources/js/components/live-score-display.tsx`, `resources/js/pages/public/scoreboard.tsx`

| Reference shows | Current implementation | Gap |
|---|---|---|
| Team logos, big score, quarter-by-quarter score table, shot clock, foul dots, timeouts remaining | `LiveScoreDisplay` — team name labels (text), big score, `fouls_a`/`fouls_b` as plain text with a "Bonus" badge past the threshold. No logos, no quarter breakdown table, no shot clock, no timeout tracking | Partial functional gap — `ScoringSession.sport_state` for basketball only stores `{fouls_a, fouls_b}` today (`ScoringSessionController`), not a per-quarter score history or timeouts/shot-clock state. A quarter-by-quarter table needs either a new `sport_state` shape (mirroring how boxing already stores `rounds[]` and softball stores `innings[]`) or deriving it from `ScoreEvent` history. Team logos need a new nullable image field on `School`/`Delegation` if pursued — not part of the schema today |
| Team Comparison stat panel (FG%, 3PT%, FT%, rebounds, assists, turnovers, steals, blocks) | Not tracked at all — `ScoringSession` has no per-stat-category state for basketball beyond fouls | Functional gap — would require a materially larger `sport_state` schema and new scoring-console UI to record these live, not just a display change |
| Live Play-by-Play feed | `ScoreEvent` already stores a full event-by-event history (`type`, `payload`, `recorded_by`, timestamp) — the data exists | Visual/composition only — this one is genuinely just a missing display, the backend already has what's needed |
| Top Performers (per-athlete points/rebounds/assists) | No per-athlete stat tracking in live scoring — entries exist, but `ScoringSession`/`ScoreEvent` never attribute a point to a specific athlete, only to a side (`side_a`/`side_b`) | Functional gap |
| Box Score / Team Stats / Videos tabs | None of these exist | Functional gap (Videos especially — no video storage/embedding anywhere in the app) |
| Mobile view: same data, bottom tab bar (Home/Schedule/Results/Live Scores/More) | Public layout has no bottom tab bar at all, and no Home/Schedule/Results/Live Scores/News/Galleries top nav either (see §7) | Visual/composition |

### 5. `desktop-softball-live-score.png` → same `LiveScoreDisplay` component

| Reference shows | Current implementation | Gap |
|---|---|---|
| Diamond graphic (runners on base), ball/strike/out indicator dots, inning-by-inning line score (R/H/E row) | `LiveScoreDisplay`'s softball branch renders `inning`/`half`/`outs`/`balls`/`strikes` as plain text and an `innings[]` list as plain text ("Inn 1: 2-0") — no diamond graphic, no dot indicators, no formatted table | Visual only — the underlying `SoftballState` shape (`inning`, `half`, `outs`, `balls`, `strikes`, `innings[]`) already has everything this reference needs except Hits/Errors (H/E), which aren't tracked in `sport_state` today |
| Team Comparison (Hits, Errors, Walks, Strikeouts, Stolen Bases, Batting Avg, Slugging %) | None of these tracked | Functional gap, same shape as basketball's stat gap above |
| Current Pitcher panel with pitch count | Not tracked | Functional gap |

### 6. `desktop-athletics-live-event.png` → **no current equivalent**

This is the single biggest structural gap in the assessment. `App\Enums\ScoreboardType` has exactly four cases: `Generic`, `Basketball`, `Boxing`, `SoftballBaseball` — **there is no Athletics-specific live board type at all**. An in-progress Athletics event today would fall back to the generic two-number scoreboard, which is a poor fit for a race/field event (no lane positions, no distance/time entry per athlete, no heat/final structure, no records panel). Everything in this reference — the animated track with a leader marker, current standings by position, field-events-live panel (Long Jump/Shot Put with best marks), meet records panel, live updates ticker — needs new backend modeling (most likely a new `ScoreboardType::Athletics` case with its own `sport_state` shape tracking per-athlete-entry position/mark, not an extension of the existing 2-side-score model) before any UI work is meaningful. Flag for whoever scopes WP-08-11 specifically: this WP cannot be "restyle an existing page," it is closer to a new feature.

### 7. Public portal shell (all reference images' chrome) → `resources/js/layouts/public-layout.tsx`

| Reference shows | Current implementation | Gap |
|---|---|---|
| Full top nav: Home / Schedule / Results / Live Scores (with LIVE badge) / News & Announcements / Galleries / About, plus a "Public View" role switcher | `PublicLayout` header — logo + a single "Sign in" / "Dashboard" button, nothing else | Visual/composition — routes for schedule (`public/schedule`... need to verify) and results already exist as pages, they're just not linked from a shared nav bar. `PublicMeetNav` exists and is used per-meet-page (`tally.tsx` uses it) but isn't the same as this global top nav |
| Colorful hero banner with athlete illustration + tagline ("Stronger Together, Champions Forever!") | `public/home.tsx` — plain heading + paragraph, no banner, no illustration, no tagline | Visual only — would need new illustration assets, which don't exist in the repo today |
| Footer: "ONE MEET. ONE SPIRIT. ONE CHAMPION." banner + social icons | `PublicLayout`'s footer — one line of plain text ("PMMS Division Edition — DepEd Schools Division Office") | Visual only |
| Galleries nav item | No gallery feature/route exists anywhere in the app | Functional gap if pursued — photo storage/upload doesn't exist; likely out of scope entirely unless explicitly requested, flagging rather than assuming |

### 8. `public-medal-tally.png` / `mobile-ranking-medal-tally.png` → `resources/js/pages/public/tally.tsx`

Same gaps as §2 (admin medal tally), plus: the public mobile reference
adds a bottom tab bar (Home/Schedule/Results/Live Scores/Ranking) that
doesn't exist anywhere in the public layout today, and a "Medals by
Sport" icon strip (Basketball/Athletics/Volleyball/Softball/Swimming
with per-sport G/S/B counts) using per-sport emoji-style icons — visual
only, the per-sport breakdown is already derivable via the existing
`sport_id` filter, just not rendered this way.

## What already works and needs no functional change

Worth stating plainly so later WPs don't second-guess it: the
authorization model (role-based nav visibility, `Gate`/policy checks
per page), the result-integrity flow (encode → validate, `EventResult`/
`ResultPlacement` never touched by live scoring), the medal tally's
derive-at-read-time computation, and the existing polling-based live
update mechanism (5-second interval, silent retry on failure) are all
correct and sufficient foundations for the restyled UI to sit on top
of. Every WP from here should preserve these exactly, per this WP's
own rules.

## Recommendations for WP-08-02 onward

1. **WP-08-02** should establish real OKLCH color tokens (a navy
   primary + gold/amber accent, reading off the reference images'
   actual colors) replacing the current zero-chroma theme — this
   unblocks the majority of the visual (non-functional) gaps above in
   one place.
2. **WP-08-06** (Eligibility Checker) needs an owner scoping decision
   before implementation, not just a restyle — see §3.
3. **WP-08-10/11/12** (live scoreboards) should be sequenced with
   awareness that Basketball/Softball gaps are partially visual +
   partially `sport_state`-schema gaps, while **Athletics has no
   existing foundation at all** and needs backend design first (§6).
4. **WP-08-07** (public shell) can reuse `PublicMeetNav`'s existing
   pattern for the new global top nav rather than building a second,
   parallel nav system.
5. Before **WP-08-13** and **WP-08-15**, get the Claude in Chrome
   extension connected and re-run a real screenshot pass — this
   report's structural findings should hold up, but exact spacing/
   alignment/color-contrast comparisons need actual renders.

## Acceptance criteria check

- Approved visual intent captured from real components (source-level,
  not screenshots — see Method note) — done.
- Real data and existing authorization preserved — nothing was
  changed; every gap above is documented against the actual current
  implementation, no screenshot values were copied into any file.
- Responsive/connectivity states — existing states (5s polling +
  silent retry on the public scoreboard, `sm:`/`lg:`/`md:` breakpoints
  already used in `dashboard.tsx` and the sidebar header) noted where
  relevant above; no new states were needed since no UI was built in
  this WP.
- No screenshot values hardcoded — confirmed; this WP made zero code
  changes.
- Tests and quality checks — see below.
- Documentation updated — this report, plus `CHECKLIST.md`.

## Test results

No application code changed — no new or updated tests apply. Existing
suite not re-run for this WP specifically (nothing it could regress);
last full run (671/671) remains current as of the DdOPAA initiative's
own closing work earlier this session.

## Quality results

N/A — no `.php`/`.tsx` files were modified by this WP. Only new
documentation files were added.

## Files created

- `docs/reports/phase-08/WP-08-01-completion.md` (this report)

## Files modified

- `docs/phases/phase-08-ui-ux-visual-alignment/CHECKLIST.md` (WP-08-01
  checked off)

## Remaining issues

- Chrome extension unavailable this session — a real screenshot pass
  is recommended before WP-08-13/WP-08-15 (see Method note).
- Three items need an explicit owner scoping decision before their WP
  can start cleanly: the Eligibility Checker's automation scope
  (§3), the Athletics live board's data model (§6), and whether
  Events-Overview/Registration-Status donut charts on the dashboard
  are worth the new backend aggregates they'd require (§1).

## Next

WP-08-02 — PMMS Design Tokens and Visual Standards, on owner
instruction (per this WP's own rule: do not begin the next work
package).
