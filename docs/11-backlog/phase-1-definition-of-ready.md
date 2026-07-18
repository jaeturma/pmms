# PMMS Phase 1 Definition of Ready

**Status:** Draft Complete — Pending Backlog, Architecture, Product, Quality, and Engineering Review
**Related:** [phase-1-definition-of-done.md](phase-1-definition-of-done.md), [phase-1-quality-gates.md](phase-1-quality-gates.md)

## 1. Global Readiness Standard

A work package is ready to start only when **all** of the following hold. Every individual work package's own Section 25 references this document and adds only work-package-specific criteria — it does not repeat these:

1. Every hard-dependency predecessor work package (per [phase-1-dependency-map.md](phase-1-dependency-map.md)) has reached **Implementation Complete** or later.
2. The work package's Architecture Sources (its Section 3) are available and unchanged since the work package document was written; if a cited source has changed, the work package must be re-validated before starting.
3. Every decision the work package's Section 6 classifies as a **Hard dependency** is resolved; every **Soft dependency** is either resolved or its absence is explicitly accepted as a documented constraint.
4. Required policy sources are either verified or explicitly marked Not Applicable — no work package touching a policy-blocked capability (Section 17 of the main backlog document) may be started, since none exist in this backlog.
5. The work package's Acceptance Criteria (Section 24) have been reviewed by at least the required reviewer(s) named in its Section 1.
6. Required reviewers for the work package are known (even if only "role identified, name pending" — see [phase-1-review-and-signoff-model.md](phase-1-review-and-signoff-model.md)).
7. Test tooling required by the work package's Section 20 is available in the environment (e.g., Pest/PHPUnit for backend work, the frontend test runner for React work, the Flutter test runner for Flutter work).
8. The required local environment (PHP/Laravel, Node, and — starting at WP-08-01/WP-09-01 — MySQL, Redis, and an S3-compatible/MinIO endpoint) is available and reachable.
9. No critical blocker remains open against the work package in [phase-1-risk-register.md](phase-1-risk-register.md) or [phase-1-decision-register.md](phase-1-decision-register.md).

## 2. Epic-Level Readiness Notes

- **EPIC-01** work packages are ready as soon as the repository is checked out; WP-01-01 has no predecessor.
- **EPIC-03/04/05** work packages additionally require that the implementer re-run WP-01-01's inspection steps if any framework version has changed since WP-01-01 last executed.
- **EPIC-08/EPIC-09** work packages that touch MySQL/Redis/MinIO require those services to be reachable in the working environment before starting — this is an environment precondition, not an architecture blocker.
- **EPIC-11/EPIC-12** accessibility-specific tasks (WP-11-12, WP-12-12) are ready to start against the provisional WCAG 2.1 AA assumption (DEC-GENERAL-01); they are not blocked pending final DX-01 resolution, but their acceptance criteria must be re-verified if the final target differs.
- **EPIC-15** work packages are ready only once every epic they review has reached Implementation Complete — see each WP-15-XX's own Section 6.

## 3. What "Ready" Does Not Mean

Ready to start is not the same as approved, funded, staffed, or scheduled — those are organizational decisions outside this backlog's scope. Ready to start also does not mean risk-free; it means the specific preconditions above are met and any remaining risk is documented in the work package's Section 29, not hidden.
