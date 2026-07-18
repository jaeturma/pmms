# WP-15-04 — Foundation Audit and Privacy Review

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-15-04 | Title | Foundation Audit and Privacy Review |
| Epic | EPIC-15 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P2 | Implementation sequence | 147 |
| Target release group | Foundation Release F | Pilot relevance | Required |
| Architecture readiness classification | Verification Only | Primary bounded context | Cross-cutting |
| Secondary affected contexts | All | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer, Privacy/data-governance reviewer |

## 2. Purpose

Verifies the audit and privacy safeguard layers (EPIC-06 and EPIC-14) as actually consumed foundation-wide: audit coverage complete for auditable operations, the three-way audit/activity/security distinction preserved in practice, metadata (actor/context/correlation) present, sensitive-view and export events firing, and the privacy regressions (classification, masking, redaction) holding across every epic's real code paths — the direct check on ARR-10 and RISK-EPIC14-01.

## 3. Architecture Sources

[../../../10-review/security-privacy-audit-and-compliance-review.md](../../../10-review/security-privacy-audit-and-compliance-review.md) (method), ADR-0006; reviews WP-06-01..09 and WP-14-01..10 output plus all consumers.

## 4. Scope

Audit-coverage sweep: every state-changing foundation operation mapped to its expected audit/activity/security events, verified by exercising representative flows; metadata completeness spot checks (actor, effective user, context, correlation on real events); distinction preservation check (no user-facing timeline data in audit_events, and vice versa); sensitive-view and export audit verification on the surfaces that exist (WP-06-07, WP-13-09, WP-14-04 reference export); WP-14-10 suite re-run plus manual review of its mapping table; log-content sampling for redaction effectiveness on real traffic patterns; review record for WP-15-12.

## 5. Explicit Exclusions

Does not fix audit or privacy defects (owning epics do); does not define retention numeric values (PD-04 remains open — verifies retention-*ready* structure only); does not perform legal/compliance certification (no authority to; evidence supports future review).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-06-01..09 and WP-14-01..10 at Implementation Complete | Hard |
| All consuming epics complete | Hard |

## 7. Current-State Inspection

Reviews fired events and written logs from exercised flows — not conventions on paper.

## 8. Proposed Implementation Direction

Operation-to-event mapping table built from the route/command inventory, then verified by exercising flows; sampled log inspection; findings register in evidence format.

## 9. Database Changes

Database Changes: None.

## 10. Backend Requirements

Review execution only.

## 11. Web Frontend Requirements

Prop-minimization (WP-10-04) spot checks on rendered pages included.

## 12. Flutter Requirements

Not Applicable (WP-15-07 covers mobile logging).

## 13. Authorization and Access Control

Audit-review interface access rules (WP-06-07/WP-06-08) spot-verified.

## 14. Security Requirements

Append-only behavior re-verified through attempted-modification tests.

## 15. Privacy and Data-Governance Requirements

This work package is the privacy verification: classification coverage, masking usage, redaction effectiveness on real paths, export controls — each with recorded evidence.

## 16. Audit and Activity Events

This work package **is** the audit review.

## 17. Event, Queue, Notification, and Real-Time Requirements

Queue-path audit context propagation (WP-04-07) spot-verified.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Log sampling doubles as observability-content verification.

## 20. Testing Requirements

WP-14-10 suite re-run; exercised-flow event verification; sampling records; no new features.

## 21. Test Data Requirements

Synthetic flows only; sampled logs from test exercising, not production.

## 22. Documentation Updates

Review record to the sign-off evidence set.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Build operation-to-event mapping; exercise representative flows | EPIC-06/14 + consumers complete | Coverage verdicts recorded |
| TASK-02 | Metadata, distinction, and append-only verification | TASK-01 | Spot-check records complete |
| TASK-03 | Re-run WP-14-10 suite; sample logs for redaction | TASK-01 | Suite green; sampling recorded |
| TASK-04 | Produce review record | TASK-02, TASK-03 | Record in evidence format |

## 24. Acceptance Criteria

- **AC-01:** Given the operation-to-event mapping, when representative flows are exercised, then every expected audit/activity/security event fires with complete metadata, or a defect is filed.
- **AC-02:** Given sampled audit records and logs, when inspected, then the three-way distinction holds and no unredacted sensitive value appears.
- **AC-03:** Given the WP-14-10 suite, when re-run in the integrated foundation, then it passes.
- **AC-04:** Given sensitive-view and export surfaces, when exercised, then their audit events fire as specified.

## 25. Definition of Ready

Per [../../phase-1-definition-of-ready.md](../../phase-1-definition-of-ready.md). EPIC-06, EPIC-14, and consumers at Implementation Complete.

## 26. Definition of Done

Per [../../phase-1-definition-of-done.md](../../phase-1-definition-of-done.md). All four criteria verified.

## 27. Completion Evidence

Per [../../phase-1-completion-evidence-standard.md](../../phase-1-completion-evidence-standard.md): mapping table with verdicts, spot-check records, suite output, sampling notes.

## 28. Rollback and Recovery Considerations

Not Applicable — review only.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-15-04-01 | Representative flows under-sample rare paths where audit gaps hide | Medium | Mapping built from full route/command inventory, not memory; unexercised entries explicitly marked unverified in the record | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-15-04 — Foundation Audit and Privacy Review.

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
- Verification only — defects route to EPIC-06/EPIC-14 work packages.
- Unexercised mapping entries are marked unverified — never assumed covered.
- No retention numeric values are decided here.
```
