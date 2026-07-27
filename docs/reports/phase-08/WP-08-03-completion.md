# WP-08-03 — Admin Application Shell and Navigation

**Status:** Complete 2026-07-27. Per this WP's own rules, WP-08-04 has
not been started.

## Repository findings

Read the required files. This WP's own listed "Reference Images" are
the same generic live-scoreboard set used by WP-08-02 (basketball/
athletics/softball/mobile ranking) — not `admin-dashboard.png`, which
is the image that actually shows the application shell (sidebar,
header, logo). Confirmed this is the same templated-doc pattern
WP-08-01 already flagged (reference lists appear copy-pasted across
WPs rather than curated per WP). Used `admin-dashboard.png`,
`admin-medal-tally.png`, and `athlete-eligibility-checker.png` instead
— every reference image that actually shows the sidebar/header — since
following the WP doc's literal list would mean building the shell
against images that never show a shell at all.

Also confirmed before starting: no backend Pest test references the
sidebar/header/nav DOM in any way (this project has no frontend
component tests), so restructuring these components carried no risk of
breaking an existing test suite — verified by grep, not assumed.

## What was found and built

Three real structural gaps versus every admin reference image, closed
in this WP:

1. **Sidebar used `variant="inset"`** (a floating, rounded, margin-gapped
   panel) — every reference shows a plain full-height sidebar flush to
   the screen edge. Changed `Sidebar` to its default `variant="sidebar"`
   in `app-sidebar.tsx`.
2. **Header had no date/time, no visible identity, and used the wrong
   token family.** `AppSidebarHeader` only had a sidebar-toggle and
   breadcrumbs; every reference shows a date/time readout and an
   avatar+name+role in the top-right. Rebuilt the header: a live clock
   (new `useClock` hook, 30-second refresh — enough for a
   "May 14, 2025 · 9:45 AM"-style readout without per-second
   re-renders) plus an avatar/name/role dropdown reusing the *existing*
   `UserMenuContent` (Settings/Logout) unchanged — no new
   authorization or menu logic, just relocated. Also fixed the header's
   border to read `--border` instead of `--sidebar-border`: it sits in
   the light main-content panel (`SidebarInset`), not inside the dark
   sidebar, and WP-08-02 gave those two token families genuinely
   different colors — before that they were coincidentally both
   neutral gray, so using the wrong one never showed.
3. **Sidebar footer held a redundant second identity menu** (`NavUser`)
   once the header gained one — no reference image shows identity in
   two places. Replaced it with a `SidebarMeetCard` showing the actual
   current meet (name, status, dates, venue) — **real data**, not the
   reference's fictional "Stronger Together, Champions Forever!"
   tagline/illustration (no such copy or asset exists in this app, and
   the rules explicitly say not to hardcode screenshot values). Backing
   data (`currentMeet`) is now shared globally from
   `HandleInertiaRequests`, guarded to authenticated requests only so
   guest/public-portal page loads never pay for the extra query — same
   "most recent non-completed meet" query `DashboardController`
   already uses, kept as its own small duplication rather than
   refactored to a shared service, to keep this shell-focused WP's
   blast radius small. `nav-user.tsx` deleted — confirmed unused
   everywhere else first (`grep`), not left as dead code.

Two smaller closes, both directly evidenced by WP-08-01's gap list:

- **Role label added.** `auth.user` previously had no human-readable
  role (`role: 'admin'`, not "Administrator"). `HandleInertiaRequests`
  now enriches the shared user payload with `role_label` (reusing
  `UserRole::label()`, already existed on the backend enum, just never
  surfaced) — used by the new header identity display. `User` and the
  shared-props (`global.d.ts`) TypeScript types updated to match.
- **Nav section label** changed from "Platform" to "Main Navigation"
  (uppercase, tracking-wide) to match every reference image's sidebar
  heading — text-only change, same nav items, same role-based
  show/hide logic, untouched.

One incidental fix, found auditing the exact component being touched:
`UserInfo`'s avatar fallback used hardcoded `bg-neutral-200`/
`dark:bg-neutral-700` instead of the semantic `bg-muted`/
`text-muted-foreground` tokens WP-08-02 established — fixed while
already in this file for the `showRole` addition; not a repo-wide
sweep (WP-08-01's list of other pages with the same hardcoded-color
pattern is unrelated to this WP's scope and untouched).

## What was deliberately NOT done

- **No notification bell.** WP-08-01 flagged this explicitly as
  needing "a decision in WP-08-03 on whether to build one or omit it,
  not just restyle" — no notification concept (model, table, or event)
  exists anywhere in this backend. Building one would be a real new
  feature, not shell/navigation work, and a non-functional bell icon
  would itself violate "do not hardcode screenshot values" by
  implying functionality that doesn't exist. Decision: omit, revisit
  only if a future WP explicitly scopes a notifications feature.
- **No manager/admin sub-grouping in the nav** — every reference shows
  one flat "Main Navigation" list, so the existing single-group
  structure (role-based items still merged into one array, just
  differently labeled) was kept rather than inventing new sub-sections
  no reference shows.
- **No dashboard content changes** — `dashboard.tsx` itself (stat
  cards, donut charts, medal tally widget) is WP-08-04's scope, not
  touched here.

## Verification

- `npx tsc --noEmit` — 0 errors, confirms the new shared `currentMeet`
  prop type and `role_label` addition are consistent everywhere they're
  used.
- Full quality gate green (below).
- **Could not get a live visual screenshot** — Claude in Chrome
  extension still disconnected this session (checked twice more:
  before and after this WP's work, in addition to WP-08-01/02's
  earlier checks). Recommend a real visual pass — sidebar color, header
  layout, meet card — before WP-08-04 builds dashboard content inside
  this shell.

## Test results

`php artisan test` — **671/671 passing**, 3,341 assertions, unchanged.
The `HandleInertiaRequests` change runs on every single request in the
app, so this full-suite pass is meaningful evidence it didn't break
anything, not just a formality.

## Quality results

| Check | Result |
|---|---|
| `vendor/bin/pint --test` | Passed |
| `vendor/bin/phpstan analyse` (level 7) | Passed, 0 errors |
| `php artisan test` | Passed, 671/671 |
| `npx eslint .` | Passed, 0 errors |
| `npm run format:check` | Passed |
| `npx tsc --noEmit` | Passed, 0 errors |
| `npm run build` | Passed |

## Files created

- `resources/js/hooks/use-clock.ts`
- `resources/js/components/sidebar-meet-card.tsx`
- `docs/reports/phase-08/WP-08-03-completion.md` (this report)

## Files modified

- `app/Http/Middleware/HandleInertiaRequests.php` — `role_label` on
  shared `auth.user`, new shared `currentMeet`
- `resources/js/components/app-sidebar.tsx` — `variant="sidebar"`,
  swapped `NavUser` for `SidebarMeetCard`
- `resources/js/components/app-sidebar-header.tsx` — clock + identity
  dropdown, fixed border token
- `resources/js/components/app-logo.tsx` — larger, more prominent
  lockup
- `resources/js/components/nav-main.tsx` — "Main Navigation" label
- `resources/js/components/user-info.tsx` — `showRole` prop, semantic
  avatar-fallback tokens
- `resources/js/types/auth.ts`, `resources/js/types/global.d.ts` —
  `role_label`, `currentMeet` typing
- `docs/phases/phase-08-ui-ux-visual-alignment/CHECKLIST.md` — WP-08-03
  checked off

## Files deleted

- `resources/js/components/nav-user.tsx` — confirmed unused elsewhere
  before deleting

## Remaining issues

- Chrome extension still unavailable — recommend a real visual check
  before WP-08-04.
- Notification bell intentionally omitted (see above) — only revisit
  if a future WP explicitly scopes real notification functionality.

## Next

WP-08-04 — Admin Dashboard Visual Implementation, on owner instruction
(per this WP's own rule: do not begin the next work package).
