# WP-11-06 — Completion Report

Public Portal Search. Status: **done**.

## Repository findings

Confirmed `ResultPlacement::result()` points to `EventResult` (not
`eventResult`), and Laravel's dotted-relation support
(`whereHas('entry.athlete', ...)`, `orWhereHas('entry.athlete.school',
...)`) works for the nested athlete/school match without needing a raw
`CONCAT()` (which would differ between MySQL and the SQLite test
driver) — matched on `first_name`/`last_name` separately with `orWhere`
instead, same cross-DB-safe approach already implicit elsewhere in this
codebase.

Confirmed the existing admin `SearchesAndPaginates` trait
(`app/Http/Controllers/Concerns/SearchesAndPaginates.php`) only supports
one level of relation nesting (`relation.column`), not the two-level
`entry.athlete.school` this WP needs — wrote the placement query
directly rather than force-fitting the trait.

**Scoping decision made before writing any query**: "schools" search
had to be scoped to schools with *real participation in this meet*
(reusing WP-11-04's `participatingSchoolIds()`), not the whole
system-wide `School` catalog — matching the same discipline every
other public route already follows (Sports only shows sports actually
contested, municipalities only shows those actually competing). A
school-name search that could surface any school in the system
regardless of this meet would be a real, if subtle, scope-boundary
violation, not just an inconsistency.

## Implementation

- `app/Http/Controllers/PortalController.php`:
  - Extracted `about()`'s inline school-count query into a new shared
    private `participatingSchoolIds(Meet $meet): Collection` helper
    (same "extract on second use" pattern as `contestedSports()` in
    WP-11-03) — `about()`'s own behavior is unchanged, just reads from
    the shared helper now.
  - New `search()` method: `Meet::published()->findOrFail()`; an empty
    `q` returns all-empty groups with zero queries; otherwise runs four
    independently-scoped queries — schools (`participatingSchoolIds()`
    + `LIKE` on name), sports (`contestedSports()` filtered in-memory
    by name, case-insensitive), announcements
    (`Announcement::published()` for this meet + `LIKE` on title), and
    validated result placements (`whereHas('result', meet_id +
    validated)` + `whereHas('entry.athlete', first/last name)` or
    `orWhereHas('entry.athlete.school', name)`), each capped
    (10/10/20) and mapped to the exact same public-safe field shape
    `results()`/`sports()`/`news()` already expose — no new field
    beyond what's already public elsewhere.
  - Added `use App\Models\School;` import.
- `routes/web.php` — one new additive route, `GET /meets/{meet}/search`
  → `PortalController::search`, named `public.search`, same
  `whereNumber('meet')` constraint as every other public meet route.
- `resources/js/pages/public/search.tsx` — new page: a search
  form (GET, `preserveState`/`preserveScroll`, same pattern
  `tally.tsx`'s filters already use), an `EmptyState` for the
  empty-query prompt and a separate one for a genuine no-match result,
  and four conditionally-rendered grouped sections (Schools as plain
  badges — no fabricated link target since no school-detail page
  exists; Sports and Results linking into `/results` pre-filtered by
  `sport_id`, the same established cross-link pattern; Announcements
  linking to `/news`).
- `npm run build` rerun to regenerate Wayfinder's `resources/js/routes/
  public/index.ts` with the new `search()` helper (gitignored,
  generated).

## Tests

New `tests/Feature/PublicSearchTest.php` (10 tests — corrected during
WP-11-09's compliance review; this line originally miscounted it as
11): guest access +
unpublished-meet 404 with all-empty groups, empty query runs no search,
no-match query returns empty groups (not an error), school search
scoped to real participants only (a same-prefix non-participating
school never appears), sport search matched by name, announcement
search scoped to this meet's published rows only (a same-title foreign-
meet announcement and an unpublished one never appear), result-
placement search by athlete name and by school name, exclusion of
encoded (unvalidated) results, exclusion of another meet's data
entirely, and a `missing()`-style field-shape check on placement rows
(no `birthdate`/`lrn`/`grade_level`).

## Quality gate

- Pest: **737/737** passed, 4,190 assertions (+10 tests, +134
  assertions over WP-11-05's baseline of 727/4,056). Re-ran
  `PublicAboutTest.php` alongside to confirm the `participatingSchoolIds()`
  extraction didn't change `about()`'s own behavior — unchanged, green.
- Pint: clean, no changes needed.
- PHPStan (level 7): 0 errors.
- ESLint (`--fix`): auto-split one combined type+value import
  (`FormEvent`/`useState`) in `search.tsx` into a type-only import —
  the only file changed.
- `tsc --noEmit`: clean.
- Prettier: `search.tsx` (this WP's own file) reformatted via a
  **targeted** `prettier --write` on that one path after the ESLint
  auto-fix; the same 2 pre-existing, unrelated drifted files from
  WP-11-02 through WP-11-05 remain untouched.
- `npm run build`: clean, rerun once more after the formatting pass to
  keep the built assets in sync with the final source.

## Documentation

- `docs/public-portal.md` — new Search page entry in the Pages section,
  plus a new paragraph in the Privacy baseline section explicitly
  confirming Search doesn't widen the existing boundary (per this WP's
  own acceptance criterion).
- `docs/phases/phase-11-public-portal-completion/CHECKLIST.md` —
  WP-11-06 checked off.

## Remaining issues

None from this WP's own scope. Search is not yet reachable from the
header nav/footer or `PublicBottomNav` — expected, per the phase's own
sequencing (WP-11-08 wires all five new pages in together).

**Unrelated observation, flagged rather than acted on**: a new untracked
directory, `docs/phases/phase-08-6-lightweight-sport-mini-portals/`
(containing one file, `PHASE-08-6-LIGHTWEIGHT-SPORT-MINI-PORTALS.md`),
appeared in the working tree during this WP that this session did not
create. This matches the exact pattern `.ai/current-phase.md` already
documents from 2026-07-26 ("something else can write to this repo
concurrently") — left entirely untouched, not investigated further
(out of this WP's scope), and reported to the owner directly rather
than assumed to be self-inflicted or silently ignored.

## Git status

`git diff --stat` against `app`/`routes`/`resources`/`package.json`/
`package-lock.json` shows exactly the expected changes: `PortalController.php`,
`routes/web.php`, the already-modified `tally.tsx` (untouched further
this WP), `package.json`/`package-lock.json` (WP-11-05's Accordion
dependency, untouched further this WP), plus new untracked
`search.tsx` and its test file. No migration touched. Not committed,
per rule.

Next: **WP-11-07 — 404 Page Visual Elevation**, awaiting owner
instruction.
