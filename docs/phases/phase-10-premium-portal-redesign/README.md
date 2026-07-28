# Phase 10 — Premium Portal Redesign (Arena-Inspired Layout & Composition)

**Status:** Planned 2026-07-28 — pending owner approval to begin
WP-10-01. This directory did not exist before this plan. Scoped via
owner Q&A the same day (`AskUserQuestion`, five decisions total — two
resolved before drafting, three resolved while drafting), the same
approach every phase since Phase 6 has used.

## Goal

Give PMMS's public portal and admin UI a premium, professional
sports-event feel — using a reference template ("Arena," a football
club site) purely as **layout/spacing/composition** inspiration, not its
HTML, CSS, colors, or branding. PMMS's existing color palette, backend,
database, routes, controllers, APIs, permissions, and all business logic
(live scoring, medal computation, athlete eligibility, tournament
workflow) are explicitly untouched. This is UI enhancement only.

This phase extends, rather than replaces, **Phase 8.5 ("Premium Sports
Experience," 10 WPs, complete and committed)** — Phase 8.5 built the
token/primitive foundation (`.bg-premium-hero`, `.text-score`/`.text-
clock`, motion tokens, `LiveBadge`/`RankBadge`/`PodiumDisplay`/
`OpeningCountdown`, kiosk mode) and restyled all 6 existing public
pages. Phase 8.5's own reports explicitly flagged what it did **not**
touch — the public shell's nav/footer ("the weakest part," in its own
words), page-level composition rhythm, the admin shared-component
layer, and any new public page. That gap is exactly this phase's scope.

## Scoped decisions (owner, 2026-07-28)

- **Gallery** (mentioned in the original brief) is **deferred entirely**
  — PMMS has no photo/media model anywhere; building one is new
  backend, contradicting "UI only." Not part of this phase.
- **Rankings** stays folded into the existing Medal Tally page
  (`tally.tsx` already has the full municipality ranking table) — no
  separate `/rankings` route.
- **Footer/Contact content**: real, already-public data only (active
  meet's venue, school year, quick links). No office-contact section —
  PMMS stores no division-office address/phone/email anywhere, and
  nothing should be invented.
- **New-page navigation**: the three new pages (Sports, News, Contact)
  go in the header nav and the new footer's quick-links column only —
  **not** added to `PublicBottomNav`, which keeps its tuned 4–5-item
  one-thumb-reach mobile design (a Phase 8.5-05 decision) intact.
- **Hero treatment**: keep the existing two-color gradient
  (`.bg-premium-hero`) exactly as it is — no new CSS accent, no new
  colors — just give it more breathing room (padding/height).

## Grounding

- Every color/status/medal token PMMS uses today (Phase 8 and Phase
  8.5) is reused verbatim. Any new `@theme` entry this phase adds must
  be a spacing/motion/typography-utility token, never a new color.
- No new npm or composer dependency is expected — matches every phase's
  own discipline (broken only once, for Reverb in Phase 7, for a real
  technical need, not a visual one). No charting library exists in this
  codebase today (confirmed); every "chart" is hand-rolled div/width
  bars, and this phase continues that rather than adding a library. No
  Framer Motion is installed (confirmed) — new animation is plain CSS,
  per the owner's own stated fallback.
- The admin shell (`app-sidebar.tsx`, the shadcn `sidebar.tsx`
  primitive, `dashboard.tsx`) is already modern — this phase polishes
  spacing/typography/accents, it does not restructure the sidebar or
  convert admin pages into a public-website look.
- The repeated table/CRUD pattern (`PageHeader` + `SearchBar` + `Table`
  + `EmptyState` + `ConfirmDialog` + `PaginationControls`) is used
  near-identically across ~15–20 resource pages — confirmed by grep:
  `PageHeader` 34 usages, `EmptyState` 38, `ConfirmDialog` 20,
  `PaginationControls` 18, `SearchBar` 14. Elevating these shared
  components once (WP-10-09) is the efficient path, not editing every
  page individually.
- A real, pre-existing accessibility gap was found while planning this
  phase (not previously audited): `resources/js/components/
  team-logo.tsx` assigns colors from a raw Tailwind palette
  (`bg-amber-500`, `bg-cyan-600`, etc.) with hardcoded `text-white`, no
  `-foreground` token pairing unlike every other medal/status color in
  this app. It's `aria-hidden` (decorative), so not a strict WCAG
  text-contrast violation, but worth measuring and fixing if it fails —
  folded into WP-10-11 rather than opened as a separate WP.

## Principles

- Layout/spacing/composition inspiration only — never Arena's actual
  HTML, CSS, colors, or branding, and never a Bootstrap conversion.
- Reuse Phase 8/8.5's existing design tokens and shared components;
  extend only when a real gap exists.
- Admin stays visually distinct from the public portal — the sidebar
  shell is polished, not replaced or converted to a public-site look.
- One work package at a time; nothing committed or pushed without
  owner instruction.

## Work Packages

| WP | Title |
|---|---|
| WP-10-01 | Arena Reference Audit and Composition Mapping |
| WP-10-02 | Public Shell Rebuild: Sticky Nav and Real Footer |
| WP-10-03 | Home Hero and Landing Composition Elevation |
| WP-10-04 | Schedule and Results Layout Rhythm |
| WP-10-05 | Live Scoreboard and Countdown Composition Refinement |
| WP-10-06 | Medal Tally Layout Refinement |
| WP-10-07 | New Public Pages: Sports, News, Contact |
| WP-10-08 | Motion and Interaction Elevation Pass |
| WP-10-09 | Admin Shared-Component Visual Polish Pass |
| WP-10-10 | Admin Sidebar and Dashboard Visual Polish |
| WP-10-11 | Accessibility, Contrast, Responsive Review, and Phase Compliance Review |

Sequence is mostly loose (WP-10-03 through WP-10-06 don't depend on each
other), with three real dependencies: WP-10-02 (shell/footer) should
land before WP-10-07 (new pages need the footer's quick-links column
and the header nav it extends), and WP-10-11 must be last.

## Visual Checkpoints

1. **After WP-10-02:** the public header stays visible while scrolling
   on desktop/tablet, and a real multi-column footer replaces today's
   one-line footer, with no overlap against the mobile bottom tab bar.
2. **After WP-10-07:** `/sports`, `/news`, and `/contact` are real,
   reachable pages built from actual PMMS data, linked from the header
   and footer.
3. **After WP-10-11:** full quality gate green, every new/adjusted color
   measured for real contrast, compliance review filed, Phase 10
   closed.

## Exclusions (deferred or explicitly out of scope)

Gallery (no photo/media model exists); a separate `/rankings` route
(stays folded into Medal Tally); a real `Tabs` primitive for
`PublicMeetNav` (flagged and deferred by Phase 8.5 twice already — the
filled/outline button convention stays); any new charting library,
Framer Motion, or other new dependency; any change to color tokens,
business logic, database schema, authorization, or the result-integrity/
medal-computation core.

## Completion

Phase 10 completes via WP-10-11 (full quality gate + compliance
review), mirroring WP-08.5-10/WP-09-03. The review report goes to this
directory; the WP log lives in `.ai/current-phase.md`.
