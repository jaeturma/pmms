# WP-15-11 — Foundation UAT and Developer Acceptance

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-15-11 | Title | Foundation UAT and Developer Acceptance |
| Epic | EPIC-15 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Large |
| Priority | P2 | Implementation sequence | 154 |
| Target release group | Foundation Release F | Pilot relevance | Required |
| Architecture readiness classification | Verification Only | Primary bounded context | Cross-cutting |
| Secondary affected contexts | All | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Product owner, Engineering lead, Quality reviewer |

## 2. Purpose

Puts the integrated foundation in front of real humans under structured scenarios — administrators exercising the admin surfaces, and developers exercising the foundation as a platform (building a small throwaway feature on its conventions) — because reviews and suites (WP-15-01..10) verify correctness, while this work package verifies *usability of the foundation as a product and as a platform*. Modeled on the Phase 0.7 acceptance architecture; GAP-13 (unassigned reviewer/owner roles) must be resolved for the acceptance roles this work package requires.

## 3. Architecture Sources

[../../../04-quality/](../../../04-quality/) (acceptance architecture), ADR-0007; [../../../10-review/architecture-review-methodology-and-evidence-model.md](../../../10-review/architecture-review-methodology-and-evidence-model.md) (evidence levels — "Formally Accepted" requires exactly this kind of session evidence).

## 4. Scope

Scripted UAT scenarios covering the admin-facing foundation surfaces (account/session flows, org/meet administration, role/permission/assignment administration UI, reference-data administration, audit review, diagnostics) executed by at least two non-implementer participants per the acceptance model, with recorded outcomes per scenario (pass/fail/friction); developer-acceptance exercise: one small, throwaway feature built strictly on the foundation's conventions by a developer who did not build the foundation, recording every point where conventions were unclear, missing, or fought the framework; mobile UAT smoke (login → home skeleton on a real device); structured findings (defects to owning epics, friction items to the debt register); acceptance record with named participants and outcomes for WP-15-12 — no simulated participants, no fabricated acceptance.

## 5. Explicit Exclusions

Does not accept sports-domain functionality (none exists); does not perform public-user testing (no public surface in Phase 1); does not merge the throwaway feature (it is disposable by definition, deleted after the exercise); does not substitute AI-simulated users for the human participants the acceptance model requires.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-15-01 through WP-15-10 | Hard |
| GAP-13 role assignments (acceptance participants named) | Hard — governance dependency |

## 7. Current-State Inspection

Runs against the reviewed, defect-triaged integrated foundation in a disposable UAT environment seeded with synthetic data.

## 8. Proposed Implementation Direction

Scenario scripts derived from each admin surface's acceptance criteria; session protocol (facilitator, note-taker, recorded outcomes); developer-exercise brief with convention-friction log template.

## 9. Database Changes

Database Changes: None (UAT environment seeded synthetically; throwaway-feature migrations never merged).

## 10. Backend Requirements

UAT environment provisioning from the documented setup path (itself verified by WP-15-10).

## 11. Web Frontend Requirements

All admin surfaces exercised through the browser as real users would.

## 12. Flutter Requirements

Mobile smoke scenario on at least one real device.

## 13. Authorization and Access Control

Scenarios include permission-denied experiences deliberately (a user attempting beyond their role) — the denial experience is part of acceptance.

## 14. Security Requirements

UAT environment isolated; participants receive synthetic credentials only.

## 15. Privacy and Data-Governance Requirements

Synthetic data exclusively; no participant enters real personal data (briefed explicitly).

## 16. Audit and Activity Events

UAT sessions double as audit-trail verification: post-session, the audit review surface must show the sessions' actions correctly.

## 17. Event, Queue, Notification, and Real-Time Requirements

Scenarios include at least one notification-producing and one real-time-updating flow.

## 18. Offline and Synchronization Requirements

Mobile smoke includes one connectivity-loss moment (airplane-mode toggle) to observe the offline messaging experience.

## 19. Observability Requirements

Facilitators use the diagnostics interface during sessions — dogfooding WP-13-09.

## 20. Testing Requirements

Scenario scripts with recorded outcomes; developer friction log; findings register.

## 21. Test Data Requirements

Synthetic UAT seed documented and reproducible.

## 22. Documentation Updates

Acceptance record (participants, scenarios, outcomes, findings) to the sign-off evidence set; convention-friction items to the documentation-debt register.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Prepare scenarios, environment, and participant assignments | WP-15-01..10 complete; GAP-13 roles named | Scripts and environment ready |
| TASK-02 | Execute admin UAT sessions | TASK-01 | Outcomes recorded per scenario |
| TASK-03 | Execute developer-acceptance exercise | TASK-01 | Friction log complete; feature discarded |
| TASK-04 | Mobile smoke session | TASK-01 | Outcome recorded |
| TASK-05 | Consolidate findings and acceptance record | TASK-02..04 | Record in evidence format |

## 24. Acceptance Criteria

- **AC-01:** Given the scenario scripts, when sessions complete, then every scenario has a recorded outcome from named human participants — none simulated.
- **AC-02:** Given the developer exercise, when complete, then the friction log exists, the throwaway feature demonstrably followed foundation conventions, and the feature is deleted.
- **AC-03:** Given post-session audit review, when checked, then the sessions' actions appear correctly in the audit surfaces.
- **AC-04:** Given the findings, when consolidated, then each is routed (defect or debt) with owner and severity.

## 25. Definition of Ready

Per [../../phase-1-definition-of-ready.md](../../phase-1-definition-of-ready.md). WP-15-01..10 complete; acceptance participants named (GAP-13 resolved for these roles).

## 26. Definition of Done

Per [../../phase-1-definition-of-done.md](../../phase-1-definition-of-done.md). All four criteria verified.

## 27. Completion Evidence

Per [../../phase-1-completion-evidence-standard.md](../../phase-1-completion-evidence-standard.md): acceptance record, friction log, findings register, session notes.

## 28. Rollback and Recovery Considerations

UAT environment disposable; throwaway feature deleted (verified in AC-02).

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-15-11-01 | GAP-13 remains unresolved, leaving no named participants and stalling Release F | High | Named as a hard governance dependency here and in the sign-off package; escalation path is the WP-15-12 open-item register | Product owner |
| RISK-WP-15-11-02 | Schedule pressure shrinks UAT to a demo, recording acceptance without real exercise | High | AC-01's named-participant, per-scenario outcome requirement; evidence model forbids demo-as-acceptance | Quality reviewer |

## 30. Open Decisions

| Decision ID | Question | Blocking Status |
|---|---|---|
| DEC-WP-15-11-01 | UAT participant roster (GAP-13) | **Blocking for this work package** — must be resolved before it starts |

## 31. Future Claude Code Execution Prompt

```text
Implement WP-15-11 — Foundation UAT and Developer Acceptance.

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
- Human participants only — never simulate or fabricate a session, outcome, or acceptance.
- The developer-exercise feature is throwaway — never merge it.
- Synthetic data exclusively; brief participants against entering real personal data.
```
