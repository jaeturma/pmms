# Phase 2 — Meet Setup & Registration

**Status:** Planned 2026-07-18 — pending owner approval. Execution has not started.

## Goal

Build the first business modules of the PMMS Division Edition: the organization and
school registry, the sports and events catalog, meet setup, delegation registration,
athlete and coach registration, event entries, and manual eligibility review — everything
a Schools Division Office needs to *set up* a Provincial Meet and register its
participants. Scheduling, scoring, medal tally, and the public portal are Phase 3–4.

## Grounding

- `docs/00-product/product-scope.md` §3 (MVP capabilities) and §1 (scope decision rules —
  integrity first, configuration over one-off code, defer what requires unavailable
  authority).
- `docs/11-backlog/phase-1-deferred-scope.md` — registration modules targeted at Phase 2;
  eligibility **adjudication automation** stays deferred (policy-dependent), so Phase 2
  ships a *manual, human-validated* review workflow only.
- Phase 1 foundations to reuse: Fortify auth, `FileUploadService` + policy,
  `AuditLogger`, shared components (PageHeader, EmptyState, ConfirmDialog, StatCard,
  ui/table), dashboard framework.

## Principles

- Simple, presentable, responsive, maintainable, secure — no enterprise complexity.
- Athletes are minors: collect minimal personal data, restrict access via policies,
  audit every sensitive view/change (Data Privacy Act awareness).
- No fake data anywhere; seeded reference data must be clearly reference content.
- MySQL is the source of truth; migrations, foreign keys, indexes, normalized design.
- One work package at a time; nothing committed or pushed without owner instruction.

## Work Packages

| WP | Title |
|---|---|
| WP-02-01 | Roles & Permissions Foundation |
| WP-02-02 | Organization & School Registry |
| WP-02-03 | Sports & Events Catalog |
| WP-02-04 | Meet Setup & Lifecycle |
| WP-02-05 | Delegation Registration |
| WP-02-06 | Athlete Registry |
| WP-02-07 | Coach & Official Registry |
| WP-02-08 | Event Entry Submission |
| WP-02-09 | Eligibility Documents & Manual Review |
| WP-02-10 | Registration Views & Search |
| WP-02-11 | Audit & Authorization Integration Review |
| WP-02-12 | Rosters & Printable Lists |
| WP-02-13 | Phase 2 Compliance Review |

Sequence is strict: each WP assumes its predecessors. WP-02-01 comes first because every
business module needs role-scoped authorization from day one.

## Visual Checkpoints

1. **After WP-02-04:** admin can browse districts/schools, the sports catalog, and create
   a meet — visible in the browser at http://pmms.app.
2. **After WP-02-08:** a delegation officer can register a delegation, athletes, and
   coaches, and submit entries end-to-end.
3. **After WP-02-12:** eligibility review queue, searchable registries, and printable
   rosters/entry lists complete the phase demo.

## Exclusions (Phase 3+ or deferred)

Scheduling/brackets/heats, score entry, result validation, medal tally, public portal,
committee operations, accreditation/QR, medical case management, finance workflows,
notifications beyond in-app flash messages, Flutter, AI, multi-tenancy, SSO.

## Completion

Phase 2 completes via WP-02-13 (full quality gate + architecture compliance review),
mirroring Phase 1's WP-01-13. Reports go to `docs/reports/phase-02/`.
