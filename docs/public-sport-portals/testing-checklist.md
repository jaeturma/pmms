# Public Sport Portals — Testing Checklist

All backend behavior is covered by `tests/Feature/PublicSportPortalTest.php`
(Pest). This phase has no frontend-unit-testing infrastructure (no Vitest/
Jest/Testing Library exists anywhere in the project — confirmed, not
assumed), so frontend-only concerns (visibility-aware polling, `<Head>`
title/meta rendering) are verified by direct source inspection instead,
documented plainly rather than faked or silently skipped.

## Covered by automated tests

- [x] `/basketball` renders the real page for the active published meet;
      unknown slugs 404.
- [x] With no active meet, the portal shows an empty state (`meet: null`),
      never an error.
- [x] An active-but-unpublished meet is treated as no active meet at all.
- [x] A live match for this sport appears as `liveNow` with a real session
      payload; multiple simultaneous live matches count the extras; an
      ended session never appears as `liveNow`.
- [x] Today's/Completed/Upcoming games are classified correctly with real
      competitor names, venue, and derived score/winner.
- [x] Games from a different sport, or a different (non-active) meet, never
      appear.
- [x] The poll endpoint (`/{sportSlug}/poll`) returns the same `liveNow`
      shape as the full page load.
- [x] Game rows carry no internal/restricted athlete fields (no birthdate,
      LRN, grade level).
- [x] Today's games are capped at 10, ordered by start time (12-candidate
      test, not just 1).
- [x] All 12 sports each resolve their own real `Sport` row, isolated from
      every other sport (9-case dataset for match-based sports, plus
      dedicated Athletics/Swimming coverage).
- [x] The live board type follows the real sport (Basketball/Boxing/
      Softball-Baseball dedicated boards, Generic fallback otherwise), and
      each sport's real `sport_state` (fouls, rounds) flows through
      unchanged.
- [x] Athletics/Swimming use real `EventSchedule`/`EventResult` data (a
      `mark`, not a fabricated two-sided score); a Chess match with no live
      session never fabricates a score.
- [x] **WP-12-08**: `canonicalUrl` is present and correct for the active-meet
      case, the no-active-meet case, and across the 9-sport isolation
      dataset (distinct per sport slug, an absolute URL via `url()`).

## Verified by direct inspection, not an automated test

- [ ] ~~Simulated `visibilitychange` pausing/resuming both polls~~ — no
      frontend test runner exists; verified by reading `usePageVisible()`'s
      listener registration/cleanup and each poll effect's dependency array
      (WP-12-06).
- [ ] ~~`<Head>` title/meta description/canonical actually render in a
      browser~~ — no browser-testing/Chrome-extension access was available
      this session (a standing gap since Phase 6); verified by reading the
      compiled JSX and confirming Inertia's `<Head>` component accepts
      arbitrary `<meta>`/`<link>` children (WP-12-08).
- [ ] ~~Accessibility (screen reader, keyboard nav)~~ — verified by reading
      component source for `aria-hidden`, heading hierarchy, and Radix's
      built-in a11y where used, not a live assistive-tech run (WP-12-07).

## Full quality gate (run for every WP this phase, including this one)

Pint, PHPStan level 7, ESLint, Prettier, `tsc --noEmit`, `npm run build`,
full Pest suite, `composer audit`, `npm audit --omit=dev`. Results for this
WP are in `docs/reports/phase-12/WP-12-08-completion.md`.
