# WP-08-07 — Public Portal Shell and Branding

**Status:** Complete 2026-07-27. Per this WP's own rules, WP-08-08 has
not been started.

## Repository findings

Read the required files. This WP's own reference-image list was again
the wrong generic set (basketball/athletics/softball/mobile-ranking) —
same recurring templated-doc issue every WP in this phase has hit.
`public-medal-tally.png` is the only reference image that actually shows
a public page (it doubles as WP-08-08's own reference too, since it's
the only public-facing screenshot available), so it's what this WP's
shell/nav/branding work was checked against.

Read `resources/js/layouts/public-layout.tsx`, `PortalController`,
`docs/public-portal.md`, and every `public/*.tsx` page before changing
anything. The existing layout was genuinely minimal: a logo/wordmark and
a Sign in/Dashboard button — no site navigation at all connecting the
portal home to a meet's schedule/results/tally pages (each meet page
does have its own secondary `PublicMeetNav`, but nothing above that
ties the whole site together).

## What was found and built

The reference's header shows: logo + tagline, a horizontal nav (Home,
Schedule, Results, Medal Tally, News & Announcements, Galleries, About),
and a "Live now" dropdown indicator, plus a colorful branded hero band
per page.

**Only real routes got nav entries.** This app has no News, Galleries,
or About pages — those three reference nav items were not built; adding
them would mean either dead links or fabricated pages, both ruled out
by "do not hardcode screenshot values."

Since `/meets/{meet}/results`, `/tally`, etc. are meet-scoped (not
standalone pages), the header nav needs a meet to link into. Added a new
guest-only shared Inertia prop, `publicNav` (`HandleInertiaRequests`) —
the same "shell-level chrome shared once via middleware, not duplicated
per controller action" pattern WP-08-03 already established for the
authenticated sidebar's `currentMeet`, mirrored here for guests:

- Resolves the most recently started **published** meet
  (`Meet::published()->orderByDesc('starts_at')->first()`).
- Counts that meet's active (non-ended) `ScoringSession`s for a "Live
  now" indicator — reuses the exact scoping
  `PortalController::liveMatches()` already established, just as a
  `count()` rather than a full row fetch (the header doesn't need full
  match details, only a number).
- Guarded to `$user === null`, mirroring `currentMeet`'s own guard in
  the opposite direction, so authenticated page loads never pay for the
  extra query.
- `null` when there are no published meets at all — the header then
  shows only "Home," never a link pointing nowhere.

`public-layout.tsx` rebuilt: the nav renders from `publicNav` (Home
always; Schedule/Results/Medal Tally only when a meet exists to link
into), active-link highlighting compares the current pathname (query
string stripped) against each item's exact route URL, and a "Live now"
badge with the real live-match count appears next to the Sign
in/Dashboard button — **only when `liveCount > 0`**, matching the
project's established "no indicator for nothing to indicate" rule
(WP-08-03's notification-bell decision). The badge links into the
meet's page, which already has the full "Live now" section (WP-07-08) —
deliberately not a dropdown listing live matches inline, to avoid two
places rendering the same live-match list.

New `resources/js/components/public-page-hero.tsx` — a reusable branded
band (gradient built from the existing `--sidebar`/`--primary` tokens
from WP-08-02, no new colors) for a page's title/description/optional
meta content. Applied to `public/home.tsx` this WP, replacing its plain
heading, as the shell's proof-of-use with real data (the same title/
description text that was already there, just re-homed into the
branded band).

## What was deliberately NOT done

- **No News & Announcements / Galleries / About pages or nav links** —
  don't exist in this app; not fabricated.
- **No "Live now" dropdown with an inline match list in the header** —
  simplified to a link into the meet page's existing live section,
  avoiding duplicated live-match rendering.
- **No hamburger/Sheet mobile nav drawer** — the real nav only has up
  to 4 items (vs. the reference's 7, several of which aren't real
  anyway), so a horizontally-scrollable row (the same pattern
  `PublicMeetNav` already uses) was enough; a full mobile drawer would
  be more machinery than 4 items warrant.
- **No footer redesign / motto banner** — the reference's "ONE MEET.
  ONE SPIRIT. ONE CHAMPION." footer tagline is decorative marketing
  copy with no counterpart anywhere in this app's real content; not
  invented, same discipline WP-08-03 applied to the sidebar's fictional
  tagline.
- **No announcement ticker bar** — the existing `PublicAnnouncements`
  section on the portal home already surfaces real announcements
  adequately; a second, shell-wide "latest announcement" ticker would
  need its own shared-prop plumbing for a purely decorative addition,
  judged out of proportion for this WP.
- **`public/results.tsx`, `public/tally.tsx`, `public/scoreboard.tsx`,
  `public/meet.tsx` were not restyled with `PublicPageHero`** — applying
  it to the home page proves the component works; wiring it into each
  of those pages' own content is that page's own later WP (WP-08-08 for
  tally).

## Verification

- `npx tsc --noEmit` — 0 errors.
- Full quality gate green (below).
- **Could not get a live visual screenshot** — Claude in Chrome
  extension still disconnected this session.
- **Could not get a live HTTP check against http://pmms.app** — same
  unresolved Apache-vhost-routing issue noted in WP-08-05/06; status
  unchanged, still not treated as a blocker.

## Test results

`vendor/bin/pest` — **681/681 passing**, 3,472 assertions (3 new tests
in `PublicPortalTest`: `publicNav` resolves to the most recently started
published meet with a correct live-match count — proven with one ended
and one active session to confirm only the active one counts; `publicNav`
is `null` when no meet is published; authenticated requests never
receive `publicNav`). Every existing public-portal test file
(`PublicResultsTest`, `PublicScheduleTest`, `PublicScoreboardTest`,
`PublicTallyTest`) re-run and unaffected.

## Quality results

| Check | Result |
|---|---|
| `vendor/bin/pint --test` | Passed |
| `vendor/bin/phpstan analyse` (level 7) | Passed, 0 errors |
| `vendor/bin/pest` | Passed, 681/681, 3,472 assertions |
| `npx eslint .` | Passed, 0 errors |
| `npm run format:check` | Passed |
| `npx tsc --noEmit` | Passed, 0 errors |
| `npm run build` | Passed |

## Files created

- `resources/js/components/public-page-hero.tsx`
- `docs/reports/phase-08/WP-08-07-completion.md` (this report)

## Files modified

- `app/Http/Middleware/HandleInertiaRequests.php` — new guest-only
  `publicNav` shared prop
- `resources/js/layouts/public-layout.tsx` — site nav, active-link
  highlighting, "Live now" indicator
- `resources/js/pages/public/home.tsx` — adopts `PublicPageHero`
- `resources/js/types/global.d.ts` — `publicNav` typing
- `tests/Feature/PublicPortalTest.php` — 3 new tests
- `docs/public-portal.md` — "Header nav & 'Live now'" section
- `docs/phases/phase-08-ui-ux-visual-alignment/CHECKLIST.md` — WP-08-07
  checked off

## Remaining issues

- Chrome extension still unavailable — recommend a real visual check
  before WP-08-08.
- The pmms.app Apache vhost routing issue (noted in WP-08-05/06) is
  still unresolved.

## Next

WP-08-08 — Public Medal Tally and Rankings Page, on owner instruction
(per this WP's own rule: do not begin the next work package). That WP
can now adopt `PublicPageHero` for the tally page's own branded band —
this WP only proved the component on the home page.
