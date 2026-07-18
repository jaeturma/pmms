# PMMS Phase 1 Definition of Done

**Status:** Draft Complete — Pending Backlog, Architecture, Product, Quality, and Engineering Review
**Related:** [phase-1-definition-of-ready.md](phase-1-definition-of-ready.md), [phase-1-quality-gates.md](phase-1-quality-gates.md), [phase-1-completion-evidence-standard.md](phase-1-completion-evidence-standard.md)

## 1. Global Completion Standard

A work package is done only when **all** of the following hold, in addition to any work-package-specific criteria in its own Section 26:

**Code**
1. Implementation matches the work package's approved Scope (Section 4) exactly — no excluded item (Section 5) was implemented.
2. Static analysis (Larastan/PHPStan for backend, `tsc --noEmit` and ESLint for frontend, the Flutter analyzer for mobile) passes with zero new errors.
3. Formatting (Pint for backend, Prettier for frontend, `dart format` for Flutter) passes.

**Database**
4. Every proposed table/column/index/constraint in Section 9 is reflected exactly in the actual migrations written, with no undocumented schema drift.
5. Migrations are reversible or their irreversibility is explicitly justified in Section 28 (Rollback and Recovery Considerations).

**Backend / Frontend / Flutter**
6. Every requirement in Sections 10–12 that was not marked "Not Applicable" has been implemented and is exercised by at least one test.

**Authorization and Audit**
7. Authorization behavior described in Section 13 is verified by both a positive test (authorized actor succeeds) and a negative test (unauthorized actor is denied).
8. Audit behavior described in Section 16 is verified — the specified events are recorded with the specified metadata.

**Privacy and Security**
9. Privacy behavior described in Section 15 is verified — no data beyond the classification's minimum-necessary scope is exposed.
10. Security requirements in Section 14 are verified — no threat named in that section is left unaddressed without an explicit, reviewed exception.

**Testing**
11. All tests required by Section 20 pass; negative paths (denial, invalid input, conflict, offline-then-reconnect where applicable) are tested, not only the happy path.

**Documentation**
12. Every documentation update named in Section 22 has been made — architecture docs, `.ai/` workspace, READMEs, and any ADR update.

**Review and Evidence**
13. No unresolved Critical or High-severity defect exists against the work package.
14. Completion evidence per [phase-1-completion-evidence-standard.md](phase-1-completion-evidence-standard.md) is attached to the work package's completion report.
15. Every required reviewer named in Section 1 has reviewed and either approved or approved-with-conditions (conditions recorded, per [phase-1-review-and-signoff-model.md](phase-1-review-and-signoff-model.md)).

**Git Hygiene**
16. The Git diff for the work package contains no unrelated changes — no drive-by refactor of unrelated files, no accidental inclusion of another work package's scope.
17. No commit is made unless explicitly instructed by whoever is directing the execution session (per working rule 19/56 discipline carried from Phase 0's own working rules).

## 2. What "Done" Does Not Mean

Done means the work package's own scope is complete and verified — it does not mean the epic is complete (that requires every constituent work package to reach Done), and it does not mean Phase 1 is complete (that requires EPIC-15's sign-off package). A work package marked Done can still be reopened if a later work package's integration testing (EPIC-15) reveals a defect in it.

## 3. Explicitly Prohibited "Done" Claims

- A work package is never marked Done on the basis of documentation alone — matching working rule 22 ("do not mark unvalidated architecture as implementation-ready") carried forward from Phase 0.13.
- A work package is never marked Done by the same session that requested review, without the named reviewer role's actual review, even informally.
- No work package in this Phase 0.14 backlog is pre-marked Done — every one begins at **Status: Planned — Not Started** (working rule 21).
