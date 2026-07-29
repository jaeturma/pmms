# WP-11-03 — Completion Report

Static Gallery Page. Status: **done**.

## Repository findings

Confirmed `PortalController::sports()`'s existing grouping query
(`meet->events()->with('sport:id,name')->groupBy('sport_id')...`) is
the real "contested sports" source and already produces exactly the
`id`/`name`/`event_count` shape a gallery of sport-identity tiles
needs — no new query was required, only a new presentation of the
same data. Confirmed `sportIcon()` (`resources/js/components/sports-
medal-strip.tsx:13`) is already exported and reusable (it was exported
specifically for WP-10-07's Sports page to reuse). Confirmed, again,
that no `Photo`/media model or migration exists anywhere in `database/
migrations/` — the same finding WP-11-01 recorded, re-verified before
writing any code rather than assumed.

## Implementation

- `app/Http/Controllers/PortalController.php` — extracted the sports-
  grouping query out of `sports()` into a new private
  `contestedSports(Meet $meet): array` helper, so `sports()` (WP-10-07)
  and the new `gallery()` (this WP) both read from one query instead of
  duplicating the grouping logic (same "extract on second use" pattern
  the project already follows for `TopByPointsCard`/`PublicLiveMatches`).
  New `gallery()` method: `Meet::published()->findOrFail()` +
  `contestedSports()`, nothing else — no new aggregate.
- `routes/web.php` — one new additive route, `GET /meets/{meet}/
  gallery` → `PortalController::gallery`, named `public.gallery`, same
  `whereNumber('meet')` constraint as every other public meet route.
- `resources/js/pages/public/gallery.tsx` — new page: aspect-square
  icon tiles (tinted `bg-primary/5` panel, `sportIcon()` centered, a
  hover scale/lift) in a responsive grid, each with a small "Results ·
  Medal tally" link pair underneath pointing to the same `sport_id`-
  filtered destinations `sports.tsx` already uses. Deliberately a
  different visual shape (square gallery tile + caption row) from
  `sports.tsx`'s horizontal list-card, so the two pages read as
  genuinely different presentations of the same real data rather than
  literal duplicates — per this WP's own "distinct presentation" intent
  and Phase 11's DESIGN-NOTES resolution that a fake-photo gallery
  would misrepresent real DepEd content.
- `npm run build` rerun to regenerate Wayfinder's `resources/js/routes/
  public/index.ts` with the new `gallery()` helper (gitignored,
  generated).

## Tests

New `tests/Feature/PublicGalleryTest.php` (4 tests, mirroring
`PublicSportsTest`'s own conventions exactly, since it's the same
underlying data): guest access + unpublished-meet 404, real per-sport
event counts, sports not attached to the meet never appearing, and a
`missing()`-style field-shape check. Re-ran `PublicSportsTest.php`
alongside it to confirm the `contestedSports()` extraction didn't
change `sports()`'s own behavior — both suites pass unchanged.

## Quality gate

- Pest: **722/722** passed, 3,982 assertions (+4 tests, +50 assertions
  over WP-11-02's baseline of 718/3,932).
- Pint: clean, no changes needed.
- PHPStan (level 7): 0 errors.
- ESLint (`--fix`): 0 issues, no files changed.
- `tsc --noEmit`: clean.
- Prettier: only the same 2 pre-existing, unrelated drifted files
  flagged as WP-11-02 (`registry/school-districts.tsx`, `registry/
  schools.tsx`) — confirmed via `git status` they're still untouched by
  this WP; nothing new to fix.
- `npm run build`: clean.

## Documentation

- `docs/public-portal.md` — new Gallery page entry in the Pages
  section.
- `docs/phases/phase-11-public-portal-completion/CHECKLIST.md` —
  WP-11-03 checked off.

## Remaining issues

None. Gallery is not yet reachable from the header nav/footer or
`PublicBottomNav` — expected, per the phase's own sequencing (WP-11-08
wires all five new pages in together).

## Git status

`git diff --stat` against `app`/`routes`/`resources` shows 3 files
changed (`PortalController.php`, `routes/web.php`, and WP-11-02's
already-modified `tally.tsx`, untouched further this WP), plus new
untracked `gallery.tsx` and its test file — no migration, no
dependency manifest touched. Not committed, per rule.

Next: **WP-11-04 — About Page**, awaiting owner instruction.
