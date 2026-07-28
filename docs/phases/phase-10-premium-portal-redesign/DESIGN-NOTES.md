# Phase 10 Design Notes

- **Arena is a layout/composition reference, never a source of assets.**
  No HTML, CSS, colors, fonts, or branding is copied — a `WebFetch`
  read of the reference during planning extracted its design
  *language* (full-bleed hero + overlay tagline, sticky nav with a CTA,
  monospace live countdown, structured cards with consistent aspect
  ratios, three-column footer, generous grid rhythm), not its markup.
  WP-10-01 formalizes this mapping before any other WP touches code.
- **Gallery deferred, not built.** PMMS has no photo/media model
  anywhere in the schema. The owner chose this over building one (a
  real backend/database addition) or a placeholder page — Gallery
  simply isn't part of this phase. Revisit as its own scoped feature
  later if wanted.
- **Rankings has no separate identity from Medal Tally.** The
  "Rankings" item in the original brief and the existing municipality
  standings table on `tally.tsx` are the same data — the owner chose to
  keep them as one page rather than add a route that would just
  duplicate it.
- **Footer/Contact show real data only, never invented office details.**
  PMMS's `Division` model has no address/phone/email field, and none
  was added — the owner explicitly declined the one schema exception
  offered (a small `Division.contact_info` field) in favor of showing
  only what's already real and public: the active meet's venue, school
  year, and links to the rest of the portal.
- **The three new pages don't touch the mobile bottom tab bar.**
  `PublicBottomNav` was deliberately kept to 4–5 items in Phase 8.5-05
  for one-thumb phone reach. Sports/News/Contact reach mobile users via
  the header nav (which already scrolls/wraps) and the new footer
  instead of growing the tab bar.
- **The hero gradient itself doesn't change.** `.bg-premium-hero`
  (Phase 8.5-02) stays exactly as defined — the "premium, full-bleed"
  feeling Arena's photography gives comes from spacing/breathing room
  in this phase, not a new visual treatment, since PMMS has no
  photography pipeline and the owner ruled out stock imagery.
- **No new dependency, anywhere, for any reason.** Confirmed no
  charting library and no Framer Motion exist in `package.json` before
  writing this plan — both stay that way. Chart-like visuals continue
  the existing hand-rolled div/width-bar approach (`EventsOverviewCard`,
  `MedalDistributionCard`), just with better spacing; new motion is
  plain CSS transitions reading from Phase 8.5's existing
  `--ease-premium`/duration tokens.
- **Admin polish is additive, not a redesign.** The sidebar shell
  (`app-sidebar.tsx`, the shadcn `sidebar.tsx` primitive) was confirmed
  already modern during planning (collapsible icon mode, cookie-
  persisted state, role-gated nav) — WP-10-09/10 elevate spacing/
  typography/accents on top of it, they don't restructure it, and must
  never make an admin page read as a public marketing page.
- **Elevate shared components once, not ~20 pages individually.** The
  table/CRUD pattern every resource index page composes from
  (`PageHeader`, `SearchBar`, `Table` wrapper, `EmptyState`,
  `ConfirmDialog`, `PaginationControls`) is close to identical across
  the admin app — confirmed by grep (`PageHeader` alone: 34 usages).
  WP-10-09 changes the shared components; the ~20 pages inherit the
  change for free.
- **`TeamLogo`'s color palette was never contrast-checked.** Found
  during planning, not previously flagged by any accessibility pass:
  its 8-color palette pairs raw Tailwind hues with hardcoded white text,
  unlike every other medal/status color in the app (which pairs a base
  token with its own `-foreground` token). It's `aria-hidden`
  (decorative), so not a strict violation, but worth measuring with the
  same OKLCH→sRGB→ratio method `docs/ui-ux/accessibility-review.md`
  already established, and fixing if it fails low-vision legibility —
  folded into WP-10-11.
