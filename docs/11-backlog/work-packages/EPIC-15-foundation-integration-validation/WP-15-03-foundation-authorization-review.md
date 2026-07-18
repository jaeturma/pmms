# WP-15-03 — Foundation Authorization Review

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-15-03 | Title | Foundation Authorization Review |
| Epic | EPIC-15 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P2 | Implementation sequence | 146 |
| Target release group | Foundation Release F | Pilot relevance | Required |
| Architecture readiness classification | Verification Only | Primary bounded context | Cross-cutting |
| Secondary affected contexts | All | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer, Architecture reviewer |

## 2. Purpose

Independently verifies the implemented authorization model (EPIC-05) against ADR-0003's requirements as actually wired through the whole foundation: every state-changing route authorized through the Decision Service path, scope rules effective, time-validity enforced, explicit denial and security holds honored, separation-of-duties mechanism working, and no surface (UI, API, jobs) trusting frontend capability flags.

## 3. Architecture Sources

[../../../10-review/identity-access-scope-and-assignment-review.md](../../../10-review/identity-access-scope-and-assignment-review.md) (method), [../../../01-architecture/authorization-decision-model.md](../../../01-architecture/authorization-decision-model.md), ADR-0003; reviews WP-05-01..12 output plus every consumer.

## 4. Scope

Route sweep: every state-changing endpoint maps to a policy/Decision-Service check (no unauthorized-by-omission routes); WP-05-12's authorization test matrix re-run and its coverage reviewed against the sweep; scope-boundary spot verification (org/meet isolation through real requests); time-validity, explicit-denial, and security-hold behaviors exercised end-to-end; SoD mechanism exercised on the foundation's own operations; grant review (seeded roles/permissions match the corrected catalogs — the TD-07/TD-08 count-consistency issue re-checked); frontend-capability-flag misuse sweep; review record for WP-15-12.

## 5. Explicit Exclusions

Does not modify the authorization model (defects route to EPIC-05); does not define module-specific SoD rule content (blocked on OD-07/09/12/15, out of Phase 1); does not review authentication (WP-15-09 covers session/authn posture).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-05-01 through WP-05-12 at Implementation Complete | Hard |
| All authorization-consuming epics complete | Hard |

## 7. Current-State Inspection

Reviews the wired reality: routes, policies, seeds, and test matrix as they exist — not the EPIC-05 documents.

## 8. Proposed Implementation Direction

Automated route-to-authorization mapping sweep plus targeted end-to-end exercises; findings register in evidence format.

## 9. Database Changes

Database Changes: None.

## 10. Backend Requirements

Review execution only.

## 11. Web Frontend Requirements

Capability-flag misuse sweep across pages (flags used for display only, never as the sole gate).

## 12. Flutter Requirements

Mobile-side capability handling reviewed in WP-15-07; API-side enforcement verified here protects both clients.

## 13. Authorization and Access Control

This work package **is** the authorization review.

## 14. Security Requirements

Fail-closed verification: unauthenticated/unauthorized access to every foundation surface denied by default; context-loss scenarios (WP-04-09's isolation tests) re-run.

## 15. Privacy and Data-Governance Requirements

Not Applicable directly (WP-15-04).

## 16. Audit and Activity Events

Authorization denials generating the expected security events (WP-03-07/WP-06-03 wiring) spot-verified.

## 17. Event, Queue, Notification, and Real-Time Requirements

Private-channel authorization (WP-09-10) included in the sweep.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

Matrix re-run, sweep outputs, end-to-end exercise records; new tests only where the sweep found untested surfaces (filed as defects to owning WPs, optionally implemented there).

## 21. Test Data Requirements

Synthetic users/roles/scopes via existing factories.

## 22. Documentation Updates

Review record to the sign-off evidence set.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Route-to-authorization mapping sweep | EPIC-05 + consumers complete | No unmapped state-changing route |
| TASK-02 | Re-run authorization test matrix; review coverage | TASK-01 | Matrix green; gaps filed |
| TASK-03 | End-to-end scope/denial/hold/SoD exercises | TASK-01 | Exercise records complete |
| TASK-04 | Grant and capability-flag sweeps; produce record | TASK-02, TASK-03 | Record in evidence format |

## 24. Acceptance Criteria

- **AC-01:** Given every state-changing route, when swept, then each maps to an explicit authorization check or is filed as a defect.
- **AC-02:** Given the scope, time-validity, explicit-denial, security-hold, and SoD exercises, when run end-to-end, then each behaves per ADR-0003 with recorded evidence.
- **AC-03:** Given seeded grants, when compared with the corrected role/permission catalogs, then they match or discrepancies are filed.
- **AC-04:** Given the capability-flag sweep, when complete, then no surface uses a frontend flag as its sole authorization gate.

## 25. Definition of Ready

Per [../../phase-1-definition-of-ready.md](../../phase-1-definition-of-ready.md). EPIC-05 and all consumers at Implementation Complete.

## 26. Definition of Done

Per [../../phase-1-definition-of-done.md](../../phase-1-definition-of-done.md). All four criteria verified.

## 27. Completion Evidence

Per [../../phase-1-completion-evidence-standard.md](../../phase-1-completion-evidence-standard.md): sweep outputs, matrix results, exercise records, findings register.

## 28. Rollback and Recovery Considerations

Not Applicable — review only.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-15-03-01 | Sweep tooling misses routes registered dynamically, giving false completeness | High | Sweep method documented with limitations; manual route-list diff as cross-check | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-15-03 — Foundation Authorization Review.

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
- Verification only — authorization defects route to EPIC-05 work packages.
- Fail-closed is the standard: any ambiguous surface counts as a finding.
- Evidence for every verdict — no unexercised claims.
```
