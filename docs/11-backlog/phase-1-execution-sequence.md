# PMMS Phase 1 Execution Sequence

**Status:** Draft Complete — Pending Backlog, Architecture, Product, Quality, and Engineering Review
**Related:** [phase-1-dependency-map.md](phase-1-dependency-map.md), [phase-1-work-package-catalog.md](phase-1-work-package-catalog.md)

## 1. Recommended Sequence

1. **Release A (Engineering and Architecture Baseline).** WP-01-01 first, always. WP-01-02, WP-01-03, WP-01-04, WP-01-05, WP-01-06 may run in parallel once WP-01-01 completes. WP-01-07 and WP-01-08 close out the release. EPIC-02 (WP-02-01 → WP-02-02 → {WP-02-03, WP-02-04, WP-02-05, WP-02-06 in parallel} → WP-02-07, WP-02-08) begins once WP-01-01 completes; it does not need to wait for the rest of EPIC-01.
2. **Release B (Identity, Context, and Authorization).** EPIC-03 (WP-03-01 → WP-03-02 → {WP-03-03, WP-03-04, WP-03-05, WP-03-06} → WP-03-07 → WP-03-08) and EPIC-04's early work packages (WP-04-01 → WP-04-02) may start in parallel once EPIC-02 completes. EPIC-04's later work packages (WP-04-03 onward) need WP-03-01. EPIC-05 begins only once WP-04-01, WP-04-02, and WP-04-03 complete.
3. **Release C (Audit, Data, Files, and Configuration).** EPIC-06 may begin as soon as EPIC-02 completes (it does not depend on EPIC-03/04/05). EPIC-07 needs WP-04-01/WP-04-02 for its org/meet reference types but otherwise proceeds independently. EPIC-08 needs WP-01-06 (storage config), WP-02-05, and WP-05-07 (for authorized download, WP-08-06 only) — earlier EPIC-08 work packages (WP-08-01 through WP-08-05) may start once WP-01-06 and WP-02-05 complete, ahead of EPIC-05 finishing.
4. **Release D (Runtime, APIs, and Web Experience).** EPIC-09's Redis/Queue/Horizon/Reverb work packages (WP-09-01, WP-09-02, WP-09-09) need only WP-01-01 and may start early, in parallel with Release B. EPIC-10 needs WP-02-07, WP-04-06, and WP-05-07. EPIC-11 needs WP-01-03 and, for its later work packages, WP-10-02/WP-10-05/WP-10-07.
5. **Release E (Mobile, Operations, and Security).** EPIC-13 needs only WP-02-08 and may start as early as Release A completes. EPIC-14 needs only WP-02-02 and may also start as early as Release A completes — in practice, EPIC-13 and EPIC-14 should start in parallel with Release B, not wait for Release D. EPIC-12 needs WP-01-05, WP-03-02, WP-10-01, and WP-11-01, so it necessarily starts later, once Releases B and part of D are underway.
6. **Release F (Integration and Foundation Sign-Off).** EPIC-15 begins only once all prior epics have reached at least "Implementation Complete" on their constituent work packages.

## 2. Critical Path

See [phase-0.14-phase-1-implementation-backlog.md, Section 14](phase-0.14-phase-1-implementation-backlog.md#14-critical-path).

## 3. Parallelizable Groups

| Group | Work Packages | Starts After |
|---|---|---|
| A1 | WP-01-02, WP-01-03, WP-01-04, WP-01-05, WP-01-06 | WP-01-01 |
| A2 | WP-02-03, WP-02-04, WP-02-05, WP-02-06 | WP-02-02 |
| B1 | WP-03-03, WP-03-04, WP-03-05, WP-03-06 | WP-03-02 |
| C1 | EPIC-06 (WP-06-01..03) | EPIC-02 |
| C2 | EPIC-07 (WP-07-02..06) | WP-07-01, WP-04-01/02 |
| D1 | WP-09-01, WP-09-02, WP-09-09 | WP-01-01 |
| E1 | EPIC-13 (WP-13-01..08) | WP-02-08 |
| E2 | EPIC-14 (WP-14-01..08) | WP-02-02 |

## 4. Blocking Decisions

| Decision | Blocks | Status |
|---|---|---|
| DX-01 (WCAG conformance target) | WP-11-12, WP-12-12 | Open — provisional WCAG 2.1 AA assumption recommended (DEC-GENERAL-01) |
| Flutter toolchain provisioning | WP-01-05, WP-12-01 | Open — `mobile/` does not exist; must be created |
| Malware-scanner selection | WP-08-08 (readiness only, non-blocking for Phase 1 completion) | Deferred |

No other Phase 1 work package is blocked by an open Phase 0 decision — every module gated by the OD-07/08/09/10/12/15 policy cluster is out of Phase 1 scope entirely (Section 17 of the main document).

## 5. Validation Checkpoints

- End of Release A: WP-01-08 confirms toolchain/documentation alignment before Release B begins in earnest.
- End of Release B: WP-05-12 (Authorization Test Matrix) gates any work package elsewhere in the backlog that depends on `WP-05-07`.
- End of Release C: WP-06-09, WP-07-11, WP-08-11 confirm audit/reference-data/file foundations independently before Release D's frontend work consumes them.
- End of Release D: WP-10-10, WP-11-13 confirm frontend foundation before EPIC-12 (Flutter) and EPIC-15 begin drawing on shared conventions (API contracts, design tokens).
- End of Release E: WP-12-12, WP-13-10, WP-14-10 confirm mobile/operations/security foundations independently.
- Release F: WP-15-01 through WP-15-11 gate WP-15-12, the single foundation sign-off checkpoint.

## 6. Release Gates

A release group is not entered until every hard dependency named in [phase-1-dependency-map.md](phase-1-dependency-map.md) for its first work package is satisfied. A release group is not exited (i.e., its epics are not treated as available foundation for later releases) until every work package in it reaches at least "Implementation Complete" with its own Definition of Done satisfied.

## 7. First Work Package

**WP-01-01 — Repository and Framework Baseline Verification.** See [phase-0.14-phase-1-implementation-backlog.md, Section 23](phase-0.14-phase-1-implementation-backlog.md#23-first-work-package) for the full justification.
