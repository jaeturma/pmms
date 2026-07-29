# WP-12-08 — Completion Report

SEO Metadata and Required Documentation. Status: **done**.

## Repository findings

Confirmed before writing anything: no page anywhere in this application
sets a meta description, canonical link, or any social-preview (Open
Graph/Twitter Card) metadata except one portal-wide `<meta
name="description">` on `PublicLayout` (WP-04-06) — this WP is the first
to need a genuinely per-page description/canonical.

## Real finding: Inertia's Head dedup needs an explicit `head-key`

Read `@inertiajs/core`'s actual compiled `head.ts` output (not assumed)
before shipping. Its `collect()` function only dedupes head elements that
carry a matching `data-inertia="<key>"` attribute; elements rendered
without an explicit `head-key` prop get `data-inertia=""`, which fails the
dedup regex (`[^"']+` requires at least one character) and so each such
element is appended as its own separate node. Concretely: adding a plain
`<meta name="description">` to `sport-portal.tsx`'s own `<Head>` would
**not** have overridden `PublicLayout`'s existing portal-wide description —
it would have rendered as a *second*, competing `<meta name="description">`
in the actual `<head>`, with no established behavior in this codebase for
which one a crawler picks. Confirmed no `head-key` usage exists anywhere
else in this codebase (this is a genuinely new pattern, not a fix for
already-wrong code). Fixed by giving both the layout's description and the
sport-portal page's own description/canonical a shared, stable `head-key`
(`"description"`, `"canonical"`) so the page-level tag correctly replaces
the layout-level default rather than duplicating it.

## What changed

- `app/Http/Controllers/PortalController.php` — `sportPortal()`'s existing
  `$base` array gained `canonicalUrl` (`route('public.sport-portal',
  $slug->value)`, an absolute URL via `APP_URL`), shared by both the
  active-meet and no-active-meet response branches.
- `resources/js/pages/public/sport-portal.tsx` — both branches' `<Head>`
  now include a `head-key="description"` meta description built from real,
  already-on-the-page sport/meet data (no fabrication), and a
  `head-key="canonical"` canonical `<link>` from the new prop. Titles were
  already real and distinct per sport (`"{Sport} — {Meet}"` /
  `"{Sport} | Provincial Meet"`) — kept as-is rather than adopting the
  original brief's own literal `"{Sport} | DdOPAA Live"` branding, which
  doesn't match this app's actual name.
- `resources/js/layouts/public-layout.tsx` — its existing portal-wide
  description gained the matching `head-key="description"` so sport-portal
  pages correctly override it instead of duplicating it (see finding
  above); every other public page's behavior is unchanged (no other page
  sets its own description, so they still see the layout-wide default,
  now stably keyed).
- No new dependency; no social-preview (Open Graph/Twitter Card) metadata
  added — the brief's own §12 makes it conditional on "if already
  supported," and nothing in this app has ever set any, so there's nothing
  existing to extend. Adding an OG/Twitter Card system from scratch, plus
  the preview images it implies, is explicitly out of this WP's rules.
- `docs/public-sport-portals/` — the brief's own §16 required documentation
  set, all 7 files: `architecture.md`, `route-map.md`,
  `data-contract-map.md` (points at the phase's own `DATA-CONTRACT-MAP.md`
  rather than duplicating it), `sport-configuration.md`,
  `performance-strategy.md`, `testing-checklist.md`,
  `implementation-summary.md`.

## Tests

Added `canonicalUrl` assertions to 3 existing cases in
`tests/Feature/PublicSportPortalTest.php` rather than new standalone tests
(the prop is additive to already-covered request paths): the active-meet
case, the no-active-meet case, and the 9-case cross-sport-isolation
dataset (confirming a distinct, correct absolute URL per sport slug). No
test exists for the `<Head>`/meta rendering itself or the `head-key` fix —
that's DOM/browser-rendering behavior with no Inertia-prop surface to
assert on via a Feature test, and the Chrome extension was unavailable
this session; verified instead by reading Inertia's own source as
described above.

## Quality gate

- Pest: **768/768** passed, 4,646 assertions (up from 4,624 at WP-12-07's
  close — 11 new `canonicalUrl` assertions across the 3 existing cases
  extended, zero new test functions).
- Pint: clean, no changes needed.
- PHPStan (level 7): 0 errors.
- ESLint (`--fix`): 0 issues, no files changed.
- `tsc --noEmit`: clean (confirms `head-key` is a valid prop on
  `@inertiajs/react`'s typed `<Head>` children).
- Prettier (`resources/`): only the same 2 pre-existing, unrelated
  drifted registry files flagged since Phase 11 (`registry/school-
  districts.tsx`, `registry/schools.tsx`) — not touched, per the
  established "don't reformat files this WP didn't touch" rule.
- `npm run build`: clean.
- `composer audit`: no security vulnerability advisories found.
- `npm audit --omit=dev`: 0 vulnerabilities.

## Documentation

- `docs/public-sport-portals/` — new directory, all 7 required files.
- `docs/phases/phase-12-lightweight-sport-mini-portals/CHECKLIST.md` —
  WP-12-08 checked off.

## Remaining issues

None found requiring further code change this WP. Standing gaps carried
forward, unchanged by this WP: Standings/Leading Scorers/Tournament
Bracket remain honest "not available yet" for every sport (no backend
work in scope this phase); sport-portal routes are still not linked from
the header nav or `PublicBottomNav` (discoverable via `/sports`/`/gallery`
only, a default pending owner review); no frontend-unit-testing
infrastructure exists, so `head-key`/meta rendering is verified by source
inspection, not a live browser check (Chrome extension unavailable this
session, a standing gap since Phase 6).

## Git status

Not committed, per rule. Changed/added this WP:
`app/Http/Controllers/PortalController.php`,
`resources/js/pages/public/sport-portal.tsx`,
`resources/js/layouts/public-layout.tsx`,
`tests/Feature/PublicSportPortalTest.php`,
`docs/public-sport-portals/*` (new),
`docs/phases/phase-12-lightweight-sport-mini-portals/CHECKLIST.md`.

Next: **WP-12-09 — Testing and Phase Compliance Review**, awaiting owner
instruction.
