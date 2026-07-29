# WP-12-03 — Completion Report

Basketball Reference Implementation. Status: **done**.

## Repository findings

Confirmed `ScoringSession::boardType()` derives the board type
automatically from `match->event->sport->name` (via `ScoreboardType::
forSport()`) — since every test match's event points at the real
`Basketball` `Sport` row, `toLivePayload()`'s `board_type` already
resolves to `'basketball'` with zero extra code, and `sport_state`
(fouls) passes through to `LiveScoreDisplay` unchanged. Verified this
directly with a dedicated test rather than assuming it, since the
Objective specifically names "Basketball fouls support" as something
to validate, not just wire up.

Re-read WP-12-02's own acceptance criteria against what was actually
tested and found two real gaps, not yet a functional bug but a real
verification gap: the 10-item game-list cap (`->take(10)`) had never
been proven with more than one candidate row per list, and
"publication scoping" (an *active* meet that isn't *published*) had
never been exercised — every WP-12-02 test either had no meet or a
fully published+active+featured one. Both closed this WP with real
tests, not just documentation claims.

## Implementation

- `resources/js/pages/public/sport-portal.tsx` — one-class addition:
  the content wrapper now carries `animate-card-in`, matching every
  other public page's entrance treatment (Phase 8.5-06) — inherits
  reduced-motion safety for free from the existing global CSS reset,
  no new work needed. This is the only actual code change this WP made;
  no genuine basketball-specific gap requiring new shared-component or
  backend work turned up.
- No change to `PortalController.php`/`routes/web.php` beyond
  WP-12-02's own additions — confirmed via `git diff --stat` showing
  the identical line counts as WP-12-02's report.

## Accessibility re-check

Re-verified every icon across all 4 new components + the page has
`aria-hidden="true"` (checked with full multi-line context, not a
single-line grep, after WP-11-06 already flagged that shortcut gives
false negatives in this codebase's JSX formatting convention) — all
correctly marked. Heading hierarchy: `PublicPageHero`'s `<h1>`, the
shared `Heading` component's `<h2>` per section, no skipped levels.
`EmptyState`'s own wrapper `aria-hidden` already covers every icon
passed through `SportPortalUnavailable`. The venue "Directions" link's
`target="_blank"` matches this app's existing convention exactly
(`nav-footer.tsx`) — no sr-only "(opens in new tab)" text anywhere else
in this codebase, so none added here either, for consistency rather
than inventing a new pattern.

## Tests

3 new tests added to `tests/Feature/PublicSportPortalTest.php`:
- Today's games cap at exactly 10 when 12 real candidate matches exist,
  ordered by start time (first and last of the visible 10 checked by
  name).
- An active-but-unpublished meet is treated identically to "no active
  meet at all" (`meet`/`liveNow` both null) — the same
  `Meet::published()` guard every other public route already enforces,
  now proven for this one too.
- A live Basketball session's `board_type` resolves to `'basketball'`
  and its `sport_state.fouls_a`/`fouls_b` pass through to the JSON prop
  unchanged.

## Quality gate

- Pest: **750/750** passed, 4,371 assertions (+3 tests, +34 assertions
  over WP-12-02's baseline of 747/4,337).
- Pint: clean, no changes needed.
- PHPStan (level 7): 0 errors.
- ESLint (`--fix`): 0 issues, no files changed.
- `tsc --noEmit`: clean.
- Prettier: only the same 2 pre-existing, unrelated drifted registry
  files — this WP's own one-line change needed no reformatting.
- `npm run build`: clean.

## Documentation

- `docs/public-portal.md` — extended the Phase 12 section with a
  "Validated end-to-end for Basketball" paragraph.
- `docs/phases/phase-12-lightweight-sport-mini-portals/CHECKLIST.md` —
  WP-12-03 checked off.

## Remaining issues

None found that require new work. Confirmed (not just assumed) that
WP-12-02's shell needed no genuine basketball-specific fix — the
Objective's "validate before generalizing" step is satisfied by the
tests and accessibility re-check above rather than by new code.
Real, honest limitation carried from every phase since Phase 6: the
Chrome extension remains unavailable this session (checked again),
so no live-browser screenshot at real phone/tablet/desktop widths was
possible — responsive/visual claims rest on the same Tailwind-class
inspection and passing-test-suite discipline every prior phase has
used, stated plainly rather than overclaimed.

## Git status

`git diff --stat` against `app`/`routes` is byte-for-byte identical to
WP-12-02's own report (0 new lines) — confirming no backend change was
needed this WP. The only new/changed file beyond WP-12-02's set is the
one-class edit already inside the (still-untracked, since it's a new
file) `sport-portal.tsx`, plus the 3 new tests. No migration, no
dependency. Not committed, per rule.

Next: **WP-12-04 — Generalize to the Remaining 11 Sports**, awaiting
owner instruction.
