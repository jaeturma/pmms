# WP-04-09 — Cross-Context Isolation Tests

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-04-09 | Title | Cross-Context Isolation Tests |
| Epic | EPIC-04 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P2 | Implementation sequence | 33 (closes EPIC-04) |
| Target release group | Foundation Release B | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | Cross-cutting |
| Secondary affected contexts | All | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer, QA lead |

## 2. Purpose

Proves WP-04-05 through WP-04-08's isolation conventions actually hold by creating a second organization/meet in a test-only scenario and confirming zero cross-tenant data leakage — the concrete evidence for RISK-GENERAL-10 and RISK-EPIC04-01's mitigation, and the closest Phase 1 gets to a tenant-isolation regression suite.

## 3. Architecture Sources

ADR-0012, [../../../10-review/multitenancy-scalability-and-enterprise-readiness-review.md](../../../10-review/multitenancy-scalability-and-enterprise-readiness-review.md).

## 4. Scope

Create a test scenario with two organizations, each with its own meet and users; verify a user of Organization A cannot read, write, or enumerate Organization B's data via any repository, API, or Inertia page exercised so far (WP-04-01 through WP-04-08's surface).

## 5. Explicit Exclusions

Does not activate multi-tenancy as a product feature (this is test-only data, not a real second tenant); does not test contexts not yet built (EPIC-05 onward) — this work package tests only what EPIC-04 itself built.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-04-01 through WP-04-08 | Hard |

## 7. Current-State Inspection

All EPIC-04 work packages' implementations, as the system under test.

## 8. Proposed Implementation Direction

Test-only work package; no production code beyond fixing any leak discovered.

## 9. Database Changes

Database Changes: None (test fixtures only, via factories).

## 10. Backend Requirements

Not Applicable beyond test code and any fix for a discovered leak.

## 11. Web Frontend Requirements

Not Applicable — no UI exists yet to test.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Not Applicable directly — EPIC-05 doesn't exist yet; this tests data-layer isolation, not authorization decisions.

## 14. Security Requirements

This work package's entire purpose is a security-verification exercise.

## 15. Privacy and Data-Governance Requirements

Not Applicable — no personal data in EPIC-04's tables.

## 16. Audit and Activity Events

Not Applicable directly.

## 17. Event, Queue, Notification, and Real-Time Requirements

Includes a test confirming WP-04-07's job-context propagation does not leak Organization A's context into an Organization B job.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

Feature/integration tests: Organization A user cannot query Organization B's meet; repository-level query for Organization B's data while resolved context is Organization A returns empty, not an error leaking existence; job dispatched under Organization A context cannot access Organization B data even if maliciously re-targeted.

## 21. Test Data Requirements

Two organizations, two meets, two users — created via factories within the test itself, not as seeded demo data.

## 22. Documentation Updates

Record isolation-test results in `.ai/engineering-baseline.md`.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Build the two-organization/two-meet/two-user test scenario | WP-04-01..08 complete | Scenario built |
| TASK-02 | Write and run isolation tests against every EPIC-04 surface | TASK-01 | All tests pass; any leak found is fixed and re-tested |

## 24. Acceptance Criteria

- **AC-01:** Given two organizations, when Organization A's user queries any EPIC-04 repository, then no Organization B data is returned.
- **AC-02:** Given a job dispatched under Organization A's context, when it executes, then it cannot access Organization B's data even if its payload is tampered with to claim Organization B.
- **AC-03:** Given any isolation-test failure discovered, when found, then it is fixed and the fix is itself re-verified by test before this work package is marked done.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-04-01 through WP-04-08 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). All three acceptance criteria verified; zero known cross-tenant leak remains open.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): full isolation-test suite output.

## 28. Rollback and Recovery Considerations

Not Applicable — test-only work package; any production fix follows its own owning work package's rollback plan.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-04-09-01 | A leak is found but only partially fixed, leaving a related path still vulnerable | Critical | AC-03 requires re-verification, not just a single passing test | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-04-09 — Cross-Context Isolation Tests.

Read the complete work-package document first.

Inspect the current repository before making changes.

Implement only the approved scope.

Do not implement excluded or deferred features.

Follow all linked architecture, security, privacy, testing, design, workflow, and operational rules.

Run the required tests and quality checks.

Update the required documentation and AI workspace files.

Do not commit unless explicitly instructed.

At completion, provide:
1. Repository findings
2. Files created
3. Files modified
4. Implementation summary
5. Database changes
6. Backend changes
7. Frontend changes
8. Flutter changes
9. Authorization and audit changes
10. Tests and quality checks
11. Risks and limitations
12. Git status

Additional restrictions specific to this work package:
- Any discovered cross-tenant leak is a Critical finding — escalate immediately, do not silently patch without flagging it in the completion report.
- Do not activate multi-tenancy as a product feature; test data only.
```
