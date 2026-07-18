# PMMS Phase 1 Review and Sign-Off Model

**Status:** Draft Complete — Pending Backlog, Architecture, Product, Quality, and Engineering Review
**Related:** [phase-1-definition-of-done.md](phase-1-definition-of-done.md), [../10-review/architecture-signoff-rules.md](../../.ai/architecture-signoff-rules.md)

No reviewer is named in this document — every role below is a candidate role, consistent with [../10-review/phase-0-final-architecture-signoff.md](../10-review/phase-0-final-architecture-signoff.md)'s own discipline. No approval is fabricated or implied anywhere in this backlog.

## 1. Work-Package Review

Every work package requires review by the reviewer role(s) named in its own Section 1 before it may be marked Implementation Complete. A review consists of: scope conformance (Section 4/5), acceptance criteria verification (Section 24), Definition of Done verification, and completion-evidence verification.

## 2. Epic Review

An epic is reviewed once all of its constituent work packages reach Implementation Complete, by the Lead architect and the epic's primary domain reviewer, checking cross-work-package consistency within the epic (e.g., all of EPIC-05's work packages together implement one coherent authorization model, not eleven disconnected pieces).

## 3. Release-Group Review

A release group (Section 8 of the main backlog document) is reviewed by the Lead architect and Engineering lead jointly before the next release group is treated as unblocked, checking the epic-level dependency assumptions in [phase-1-dependency-map.md](phase-1-dependency-map.md) actually held.

## 4. Architecture Review

Performed by the Lead architect against [../10-review/architecture-fitness-functions-and-validation-gates.md](../10-review/architecture-fitness-functions-and-validation-gates.md)'s candidate checks, applied for the first time against real code once EPIC-02 completes.

## 5. Security Review

Performed by the Security reviewer, focused on EPIC-03, EPIC-05, EPIC-08, EPIC-09 (private channels), and EPIC-14 — culminating in WP-15-09.

## 6. Privacy Review

Performed by the Privacy reviewer, focused on EPIC-06, EPIC-08, and EPIC-14 — culminating in WP-15-04.

## 7. QA Review

Performed by the QA lead against every epic's closing test work package, culminating in WP-15-08 and WP-15-11.

## 8. UX Review

Performed by the UX/Accessibility reviewer, focused on EPIC-10 and EPIC-11 — culminating in WP-15-05.

## 9. Operations Review

Performed by the DevOps/Operations lead, focused on EPIC-01, EPIC-09, and EPIC-13 — culminating in WP-15-10 (documentation/runbook alignment) and contributing to WP-15-08 (performance baseline).

## 10. Acceptance Outcomes

Each review produces one of: **Approved**, **Approved with Conditions** (conditions recorded against the work package's Section 30, Open Decisions), **Requires Rework**, or **Rejected**. A work package with any open Critical-severity finding cannot receive an unconditional Approved outcome, mirroring [../../.ai/architecture-signoff-rules.md](../../.ai/architecture-signoff-rules.md).

## 11. Conditional Acceptance

"Approved with Conditions" must state the specific condition(s) and, where applicable, the target work package or decision that must resolve them — an unconditional-sounding "approved with conditions" without stated conditions is not permitted.

## 12. Exception Process

An architecture or scope exception (a deliberate, reviewed deviation from this backlog's approved scope) requires: a written justification, the specific work package(s) affected, an expiry or re-review trigger, and sign-off from the Lead architect. No exception may expand scope into Phase 1's exclusions (Section 5 of the main backlog document) without also amending this backlog document and, if the change is architecturally significant, filing a new ADR.

## 13. No Fabricated Reviewers or Approvals

Every reviewer field in every work package and epic in this backlog reads a role, not a name, and every review status defaults to not-yet-reviewed. This document does not assign, imply, or simulate any actual review having occurred.
