# PMMS Phase 1 Quality Gates

**Status:** Draft Complete — Pending Backlog, Architecture, Product, Quality, and Engineering Review
**Related:** [phase-1-definition-of-done.md](phase-1-definition-of-done.md)

No CI files are created by this document — every gate below describes a check that must eventually run, via whatever CI mechanism WP-01-07 establishes during its own future execution. This document defines *what* must be checked, not the YAML that checks it.

## 1. Local Gates

Run before any work package is proposed as Implementation Complete: `composer run lint:check` (Pint), `composer run types:check` (Larastan/PHPStan), `composer run test` (Pest/PHPUnit), `npm run lint:check` (ESLint), `npm run format:check` (Prettier), `npm run types:check` (tsc), and, once EPIC-12 begins, `flutter analyze` and `flutter test`.

## 2. Pull-Request Gates

Every change proposed for review must pass all Local Gates, plus: no unrelated file changes (Git hygiene, Definition of Done item 16), a completed work-package Section 27 (Completion Evidence) attached to the PR description, and at least one required reviewer (per the work package's Section 1) requested.

## 3. Migration Gates

Every migration must be reversible or carry an explicit, reviewed justification for irreversibility (Section 28 of the originating work package); every migration must be run against a disposable database in the review process before merge, not only asserted to work.

## 4. Authorization Gates

Every work package touching Section 13 (Authorization and Access Control) must include both a positive and a negative authorization test (Definition of Done item 7) before it can pass this gate; no work package may rely on frontend visibility as an authorization control (working rule embedded in EPIC-10's purpose).

## 5. Privacy Gates

Every work package touching Section 15 (Privacy and Data-Governance Requirements) must demonstrate that no data beyond the declared classification's minimum-necessary scope appears in logs, exports, API responses, or Inertia props (cross-referenced against EPIC-14's masking/redaction/minimization work packages).

## 6. Audit Gates

Every work package touching Section 16 (Audit and Activity Events) must demonstrate the specified events are recorded with actor/context/correlation metadata (WP-06-04's contract) before merge.

## 7. Frontend Gates

Every React work package must pass ESLint, Prettier, and `tsc --noEmit`, and must not introduce a component that duplicates an existing `resources/js/components/ui` primitive without justification (reuse-first discipline).

## 8. Flutter Gates

Every Flutter work package must pass `flutter analyze` and `flutter test`, and must not implement a full operational module (scanning, accreditation, scoring capture) — only foundation/skeleton scope, per EPIC-12's exclusions.

## 9. Accessibility Gates

Every EPIC-11/EPIC-12 UI work package must be checked against the provisional WCAG 2.1 AA target (DEC-GENERAL-01) using at least automated tooling (e.g., axe or equivalent) plus a manual keyboard-navigation pass, per WP-11-12/WP-12-12.

## 10. Integration Gates

No release group (Section 8 of the main backlog document) is treated as available foundation for the next release group until every work package in it has independently passed its own quality gates — release groups are not gated by wall-clock time, only by evidence.

## 11. Release-Group Gates

Each release-group boundary (A→B, B→C, C→D, D→E, E→F) requires the corresponding EPIC-15 review work package(s) most relevant to that group's content to have at least begun, surfacing any cross-epic integration defect before the next release group builds on top of it.

## 12. Foundation Sign-Off Gates

Phase 1 foundation is not declared complete until WP-15-12 (Phase 1 Foundation Sign-Off Package) is produced, following the same non-fabrication discipline as [../10-review/phase-0-final-architecture-signoff.md](../10-review/phase-0-final-architecture-signoff.md) — no reviewer approval is asserted without it actually occurring.
