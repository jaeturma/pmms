# WP-06-05 — Sensitive View and Export Audit Conventions

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-06-05 | Title | Sensitive View and Export Audit Conventions |
| Epic | EPIC-06 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 50 |
| Target release group | Foundation Release C | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | BC-32 Audit and Compliance |
| Secondary affected contexts | EPIC-08, EPIC-14 | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Privacy reviewer, Security reviewer |

## 2. Purpose

Defines the convention that *viewing* (not just modifying) certain classified data, and *exporting* any data, must itself be an audit event — per ADR-0006's sensitive-view-and-export discipline, since a data breach can occur through read access alone.

## 3. Architecture Sources

[../../../../03-security/](../../../../03-security/), ADR-0006.

## 4. Scope

Document which categories of view/export are audit-worthy (proposed: any view of a full user profile beyond name/email, any file download via WP-08-06, any data export); define an event-type naming convention (`viewed.*`, `exported.*`) distinguishing these from mutation events.

## 5. Explicit Exclusions

Does not implement any concrete sensitive-view/export trigger for a real capability (no such capability exists in Phase 1 beyond WP-05-11's assignment admin page and WP-08-06's file download); this work package establishes the convention only.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-06-01, WP-06-04 | Hard |

## 7. Current-State Inspection

No sensitive-view/export tracking exists.

## 8. Proposed Implementation Direction

Document the convention; apply it concretely for the first time in WP-08-06 (authorized file download) and WP-05-11 (assignment admin page, if it displays any sensitive user detail beyond name/email).

## 9. Database Changes

Database Changes: None (uses `audit_events` from WP-06-01/06-04).

## 10. Backend Requirements

Convention only; no new table.

## 11. Web Frontend Requirements

Not Applicable in this work package.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Not Applicable directly.

## 14. Security Requirements

The convention itself does not weaken any access control — it is purely additive observability.

## 15. Privacy and Data-Governance Requirements

This work package is itself a privacy control: recording who viewed/exported sensitive data is a foundational transparency and accountability mechanism.

## 16. Audit and Activity Events

This work package defines the `viewed.*`/`exported.*` event-type convention.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable directly.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable directly.

## 20. Testing Requirements

Not Applicable in this work package — no concrete trigger yet; verified once WP-08-06/WP-05-11 use the convention.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record the sensitive-view/export convention in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Document which view/export categories are audit-worthy for Phase 1's scope | WP-06-01, WP-06-04 complete | Categories documented |
| TASK-02 | Document `viewed.*`/`exported.*` naming convention | TASK-01 | Convention documented |

## 24. Acceptance Criteria

- **AC-01:** Given the convention, when reviewed, then it explicitly lists which Phase 1 capabilities (file download, assignment admin page) must apply it.
- **AC-02:** Given the naming convention, when reviewed, then `viewed.*` and `exported.*` are clearly distinguished from mutation event types.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-06-01, WP-06-04 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): the documented convention.

## 28. Rollback and Recovery Considerations

Not Applicable — documentation only.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-06-05-01 | WP-08-06/WP-05-11 forget to apply this convention when implemented | Medium | Both work packages' own Section 16 explicitly cross-reference this convention | Privacy reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-06-05 — Sensitive View and Export Audit Conventions.

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
- Documentation only — no concrete trigger implemented here.
```
