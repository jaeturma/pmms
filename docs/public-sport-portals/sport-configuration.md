# Public Sport Portals — Sport Configuration

Two parallel, hand-synced sources define the 12 sports. There is
deliberately no single shared JSON/PHP config consumed by both sides — the
backend enum is authoritative for routing/data, the frontend config is
authoritative for presentation only, and each was kept in the language/layer
that already owns that concern.

## Backend: `App\Enums\SportPortalSlug`

A 12-case PHP enum, slug ⇄ real `Sport.name` (e.g. `Basketball = 'basketball'`,
`sportName()` returns `'Basketball'`). This is the **source of truth** for:

- Which 12 slugs the two routes (`{sportSlug}`, `{sportSlug}/poll`) accept
  (`->whereIn('sportSlug', SportPortalSlug::values())`).
- Which real `Sport` catalog row a slug resolves to
  (`Sport::query()->where('name', $slug->sportName())->first()`).

## Frontend: `resources/js/config/sport-portals.ts`

A `Record<string, SportPortalConfig>`, one entry per slug, presentational
only:

| Field | Purpose | Current value |
|---|---|---|
| `slug`, `name` | Mirrors the backend enum for display | matches `SportPortalSlug` exactly |
| `scoringType` | `'team-score' \| 'sets' \| 'combat' \| 'race' \| 'time-distance' \| 'rank-only'` | one per sport, informational only — does not gate which component renders |
| `supportsStandings` / `supportsLeadingScorers` / `supportsBracket` | Whether real backing data exists for that section | **`false` for every sport, today** — see `data-contract-map.md` §D-F. Never flip to `true` without the backend actually computing that data first. |
| `terminology.game` / `.period` / `.points` | Per-sport wording ("game"/"match"/"bout", "quarter"/"set"/"inning"/"round", "points"/"runs"/"goals") | consumed by `sport-portal.tsx`'s section headings and empty-state copy via `pluralize()`/`capitalize()` (`resources/js/lib/utils.ts`) |

## Sport-specific exceptions (WP-12-05)

- **Athletics, Swimming**: no real `EventMatch` usage in this system —
  routed through `individualEventSportPortalData()` instead, reading
  `EventSchedule`/`EventResult` directly (the same source `athletics()`
  already used). Game rows carry a `mark` field (the real recorded time/
  distance) instead of a two-sided score; `side_b` is always `null` — no
  fabricated "vs" line.
- **Boxing, Chess**: genuinely head-to-head; both fit the generic
  `EventMatch`-based shape with zero functional change. Boxing keeps its
  dedicated round-history live board; Chess uses the Generic board and
  never fabricates a score when no live session exists.

## Adding a 13th sport (for a future maintainer)

1. Confirm a real `Sport` catalog row exists (or add one via the normal
   sports-catalog admin flow — out of this phase's scope).
2. Add a case to `App\Enums\SportPortalSlug` (slug + `sportName()` mapping).
3. Add a matching entry to `resources/js/config/sport-portals.ts`
   (terminology + scoring type; leave the three `supports*` flags `false`
   unless the backing data genuinely exists by then).
4. If the sport has no real `EventMatch` usage (an individual-event sport
   like Athletics/Swimming), add its `Sport.name` to
   `PortalController`'s private `INDIVIDUAL_EVENT_SPORTS` constant so it
   branches to `individualEventSportPortalData()` instead of the generic
   match-based path.

No route change is needed beyond step 2 — both routes already accept every
value in `SportPortalSlug::values()`.
