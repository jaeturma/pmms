# WP-11-01 — Arena Gap Audit, Design Analysis, and Migration Plan

## Project
Provincial Meet Management System (PMMS) — Division Edition

## Phase
Phase 11 — Public Portal Completion (Rankings, Gallery, About, FAQs,
Search, 404)

## Visual Direction
Arena Sports Template (https://uicookies.com/demo/theme/arena/) — layout,
spacing, composition, and user-experience inspiration only. Never its
HTML, CSS, colors, fonts, or branding.

## Required Reading
```text
.ai/project-rules.md
.ai/current-phase.md
.ai/work-package-runner.md
.ai/ui-ux-rules.md
.ai/skills/pmms-premium-design-system.md
.ai/skills/pmms-public-portal-experience.md
docs/ui-ux/premium-design-system.md
docs/ui-ux/shared-components.md
docs/public-portal.md
docs/phases/phase-10-premium-portal-redesign/ (README, DESIGN-NOTES, WP-10-01, WP-10-07)
docs/phases/phase-11-public-portal-completion/README.md
docs/phases/phase-11-public-portal-completion/DESIGN-NOTES.md
```

## Rules
- Inspect the repository first.
- Do not copy Arena's HTML, CSS, colors, fonts, or branding.
- Do not import Bootstrap or any new dependency.
- Preserve PMMS's existing color palette, backend, routes, controllers,
  authorization, and all business logic.
- No code changes in this WP — audit and documentation only.
- Do not commit or push.
- Do not begin the next work package.

## Exclusions
Gallery photo/media model, office-contact content, `PublicBottomNav`
item-count changes, any new dependency — all already resolved as out of
scope for this phase (README.md/DESIGN-NOTES.md).

## Objective
Produce the three deliverables the owner's original brief requires
before any code changes:

1. **Design Analysis** — re-inspect the Arena reference directly
   (`WebFetch`) rather than trusting Phase 10's notes from memory, and
   record honestly what page types it actually offers versus what the
   brief asked for (Gallery/About/FAQs/Search/404 — none of which exist
   as separate pages in the real reference, which is a single page with
   in-page anchors). Extract the design *language* that does transfer:
   card grid shape/aspect ratio, section rhythm, header hierarchy.
2. **Component Mapping** — map each of the six target pages (Rankings,
   Gallery, About, FAQs, Search, 404) to PMMS's actual current state:
   what already exists to reuse (shared components, services, data
   sources), what's a real gap, which later WP closes it.
3. **PMMS Migration Plan** — confirm this phase's WP-11-02 through
   WP-11-09 breakdown is the right shape given the mapping, or flag any
   adjustment needed before WP-11-02 starts.

## Acceptance Criteria
- Arena reference re-inspected directly (not assumed from a prior
  phase's notes).
- No code changes.
- The mapping is concrete (real file/component/model names), not
  generic adjectives.
- `docs/ui-ux/premium-design-system.md` extended with this phase's
  mapping table (same convention WP-10-01 used).
- No commit or push performed.

## Completion Report
Create:
```text
docs/reports/phase-11/WP-11-01-completion.md
```

Report repository findings, files created/modified, the mapping itself
(or a pointer to where it lives), remaining issues, git status, and next
work package.

Next:
```text
WP-11-02 — Rankings Page (Split from Medal Tally)
```
