# WP-15-09 — Foundation Security Review

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-15-09 | Title | Foundation Security Review |
| Epic | EPIC-15 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P2 | Implementation sequence | 152 |
| Target release group | Foundation Release F | Pilot relevance | Required |
| Architecture readiness classification | Verification Only | Primary bounded context | Cross-cutting |
| Secondary affected contexts | All | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer (lead), Engineering lead |

## 2. Purpose

The consolidated security review of the integrated foundation: authentication and session posture (EPIC-03 + WP-14-06/07 output), abuse controls, header/cookie/CSP posture, secret hygiene, error-leak resistance, health-surface exposure, and an integrated attack-surface walk-through against the Phase 0.6 threat model's foundation-relevant scenarios — the last security gate before sign-off.

## 3. Architecture Sources

[../../../03-security/threat-model.md](../../../03-security/threat-model.md), [../../../03-security/trust-boundaries-and-attack-surface.md](../../../03-security/trust-boundaries-and-attack-surface.md), [../../../03-security/security-testing-and-assurance.md](../../../03-security/security-testing-and-assurance.md), ADR-0006; consolidates over WP-03-08, WP-14-01..10, WP-15-03, WP-15-04 outputs.

## 4. Scope

Attack-surface inventory of the integrated foundation (routes, endpoints, channels, upload paths, health surfaces) checked against the trust-boundary documentation; authentication posture walk-through (credential handling, 2FA/passkey behavior preserved from baseline, lockout, reset flows) exercised adversarially (wrong tokens, replayed tokens, expired sessions, tampered cookies); upload-path hardening verification (WP-08-03/04 validation exercised with hostile inputs: oversized, mistyped, double-extension, embedded-content files); error-leak sweep (induced failures across surfaces, responses inspected for leakage); re-run of the WP-14-10 suite and WP-14-06/07 regressions in the integrated state; secret sweep re-run (WP-14-08); prioritized findings register (severity-rated); review record for WP-15-12.

## 5. Explicit Exclusions

Does not fix defects (owning epics do); is not a penetration test by an independent party (documented as internal review; external testing remains the Phase 0.6 assurance plan's future step); does not review deferred surfaces (public portal, external API, SMS/push — none exist).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-14-01..10, WP-03-01..08 at Implementation Complete | Hard |
| WP-15-03, WP-15-04 complete (their records feed this review) | Hard |

## 7. Current-State Inspection

Adversarial exercises against the running integrated application — the review's core method.

## 8. Proposed Implementation Direction

Structured checklist derived from the threat model's foundation-relevant scenarios; hostile-input exercise scripts; severity-rated findings register in evidence format.

## 9. Database Changes

Database Changes: None.

## 10. Backend Requirements

Review execution only; hostile-input scripts kept in the test tree marked as security exercises.

## 11. Web Frontend Requirements

Cookie/storage handling in the browser client inspected (no token in localStorage, etc.).

## 12. Flutter Requirements

Mobile-specific findings from WP-15-07 folded into the consolidated register — not re-executed.

## 13. Authorization and Access Control

WP-15-03's record is input; this review re-tests only the highest-severity paths.

## 14. Security Requirements

This work package **is** the consolidated security review; its own discipline: every claim evidence-backed, every exercise scripted and repeatable.

## 15. Privacy and Data-Governance Requirements

WP-15-04's record is input; privacy findings fold into the consolidated register.

## 16. Audit and Activity Events

Verify adversarial exercises themselves generated the expected security events (WP-06-03) — detection is part of posture.

## 17. Event, Queue, Notification, and Real-Time Requirements

Channel-authorization adversarial checks (forged channel names, unauthorized subscriptions) included.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Health-surface exposure checks (WP-13-03/04 no-leak guarantees) re-verified adversarially.

## 20. Testing Requirements

Exercise scripts, re-run suites, severity-rated register; repeatable invocations documented.

## 21. Test Data Requirements

Hostile inputs are synthetic; exercises run in disposable environments.

## 22. Documentation Updates

Consolidated security review record to the sign-off evidence set; explicitly states internal-review status and the external-testing recommendation.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Attack-surface inventory vs. trust boundaries | Dependencies complete | Inventory recorded |
| TASK-02 | Adversarial authentication/session exercises | TASK-01 | Exercise records complete |
| TASK-03 | Hostile-upload and error-leak exercises | TASK-01 | Records complete |
| TASK-04 | Re-run security suites; verify detection events | TASK-02, TASK-03 | Suites green; events verified |
| TASK-05 | Consolidated severity-rated register and record | TASK-04 | Record in evidence format |

## 24. Acceptance Criteria

- **AC-01:** Given the attack-surface inventory, when checked against the documented trust boundaries, then every surface is accounted for or filed as a finding.
- **AC-02:** Given the adversarial exercises, when executed, then no exercise achieves unauthorized access, data leakage, or undetected abuse — or the failure is filed severity-rated.
- **AC-03:** Given the adversarial exercises, when security-event records are checked, then expected detections fired.
- **AC-04:** Given the review record, when submitted, then it states internal-review status explicitly and carries the external-testing recommendation.

## 25. Definition of Ready

Per [../../phase-1-definition-of-ready.md](../../phase-1-definition-of-ready.md). Dependencies at Implementation Complete; WP-15-03/04 records available.

## 26. Definition of Done

Per [../../phase-1-definition-of-done.md](../../phase-1-definition-of-done.md). All four criteria verified.

## 27. Completion Evidence

Per [../../phase-1-completion-evidence-standard.md](../../phase-1-completion-evidence-standard.md): inventory, exercise records, suite outputs, severity-rated register.

## 28. Rollback and Recovery Considerations

Not Applicable — review in disposable environments.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-15-09-01 | Internal review mistaken for independent assurance | High | AC-04's explicit status statement; sign-off package carries the distinction forward | Security reviewer |

## 30. Open Decisions

| Decision ID | Question | Blocking Status |
|---|---|---|
| DEC-WP-15-09-01 | Timing/commissioning of independent external security testing | Non-blocking for Phase 1 close — recommendation recorded |

## 31. Future Claude Code Execution Prompt

```text
Implement WP-15-09 — Foundation Security Review.

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
- Verification only — defects route to owning work packages.
- All adversarial exercises in disposable environments, scripted and repeatable.
- State internal-review status honestly — never imply independent assurance.
```
