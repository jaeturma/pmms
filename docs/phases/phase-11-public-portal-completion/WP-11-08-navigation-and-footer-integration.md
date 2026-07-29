# WP-11-08 — Navigation and Footer Integration for New Pages

## Project
Provincial Meet Management System (PMMS) — Division Edition

## Phase
Phase 11 — Public Portal Completion

## Visual Direction
Arena's sticky nav + three-column footer, already built in Phase 10
(WP-10-02) — this WP only extends the shared nav-items array both
already read from.

## Required Reading
```text
.ai/project-rules.md
.ai/work-package-runner.md
resources/js/layouts/public-layout.tsx
resources/js/components/public-footer.tsx
resources/js/components/public-bottom-nav.tsx
app/Http/Middleware/HandleInertiaRequests.php (publicNav)
docs/public-portal.md
docs/phases/phase-11-public-portal-completion/README.md
docs/phases/phase-11-public-portal-completion/DESIGN-NOTES.md
docs/reports/phase-11/WP-11-02-completion.md
docs/reports/phase-11/WP-11-03-completion.md
docs/reports/phase-11/WP-11-04-completion.md
docs/reports/phase-11/WP-11-05-completion.md
docs/reports/phase-11/WP-11-06-completion.md
```

## Rules
- Inspect the repository first.
- Run only after WP-11-02 through WP-11-06 are all done (all five new
  pages must exist).
- Add Rankings/Gallery/About/FAQs/Search to the header nav and the
  footer's quick-links column only — reuse the same shared nav-items
  array WP-10-02/WP-10-07 already established, do not duplicate it.
- **`PublicBottomNav` is not touched** — stays at its tuned 4–5-item
  one-thumb-reach design (Phase 8.5-05/Phase 10 decision), same rule
  every prior new-page WP has followed.
- No new dependency, no new migration, no backend change.
- Run all quality checks.
- Update documentation.
- Do not commit or push.
- Do not begin the next work package.

## Exclusions
Any change to `PublicBottomNav`'s item count; any new nav-array
duplication; any change to an existing page's own content.

## Objective
Wire all five new pages into the one shared nav-items array the header
and footer already both read from, so every new destination is
reachable without growing the mobile bottom tab bar.

## Acceptance Criteria
- All five new routes appear in header nav and footer quick-links.
- `PublicBottomNav` item count and content unchanged (test asserts this
  explicitly).
- No duplicate nav-array definition introduced.
- Responsive and accessible (nav wraps/scrolls correctly with the
  larger item count).
- Tests added/updated for nav presence.
- Full quality gate green.
- Documentation updated (`docs/public-portal.md`'s header-nav section).
- No commit or push performed.

## Completion Report
Create:
```text
docs/reports/phase-11/WP-11-08-completion.md
```

Next:
```text
WP-11-09 — Accessibility, Responsive Review, and Phase Compliance Review
```
