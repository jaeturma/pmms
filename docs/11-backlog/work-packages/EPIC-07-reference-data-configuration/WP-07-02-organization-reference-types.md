# WP-07-02 — Organization Reference Types

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-07-02 | Title | Organization Reference Types |
| Epic | EPIC-07 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Small |
| Priority | P1 | Implementation sequence | 56 |
| Target release group | Foundation Release C | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | BC-34 Configuration and Reference Data |
| Secondary affected contexts | BC-03 | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Domain reviewer |

## 2. Purpose

Implements the `organization_types` reference table (e.g., Division, Provincial Office, School — proposed pending DD-09) that WP-04-01's `Organization.type` field references, following WP-07-01's generic pattern.

## 3. Architecture Sources

[../../../../01-architecture/domain-open-decisions.md](../../../../01-architecture/domain-open-decisions.md) (DD-09).

## 4. Scope

Implement `organization_types` table per WP-07-01's pattern; seed with a provisional set of common DepEd organizational levels, explicitly labeled provisional pending DD-09.

## 5. Explicit Exclusions

Does not resolve DD-09 (organization data source); does not retroactively enforce this FK on WP-04-01's `Organization.type` column if it was left nullable.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-07-01, WP-04-01 | Hard |
| DD-09 | Soft — provisional seed data pending resolution |

## 7. Current-State Inspection

`organizations.type` column exists per WP-04-01, currently nullable/unconstrained.

## 8. Proposed Implementation Direction

`organization_types` table per WP-07-01's pattern; seeded with a provisional list (e.g., Division Office, Provincial Office, School — explicitly marked "provisional, pending DD-09").

## 9. Database Changes

Proposed `organization_types` table: `id`, `code`, `label`, `active`, `sort_order`, timestamps.

## 10. Backend Requirements

Seeder only; no new application logic.

## 11. Web Frontend Requirements

Not Applicable in this work package.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Not Applicable directly.

## 14. Security Requirements

Not Applicable.

## 15. Privacy and Data-Governance Requirements

Not personal data.

## 16. Audit and Activity Events

Not Applicable directly.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

Seeder test confirming the provisional set is seeded correctly.

## 21. Test Data Requirements

The seeder is provisional reference data, explicitly labeled as such.

## 22. Documentation Updates

Record the provisional status in `.ai/architecture.md` addendum, cross-referencing DD-09.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement `organization_types` migration and seeder (provisional data) | WP-07-01, WP-04-01 complete | Seed test passes |

## 24. Acceptance Criteria

- **AC-01:** Given the `organization_types` table, when seeded, then it is explicitly documented as provisional pending DD-09, not presented as final/authoritative.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-07-01, WP-04-01 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Criterion verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): migration status, seed test output.

## 28. Rollback and Recovery Considerations

Migration reversible; seeder idempotent.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-07-02-01 | Provisional data is mistaken for authoritative once DD-09 resolves differently | Low | Explicit "provisional" labeling in seeder comments and documentation | Domain reviewer |

## 30. Open Decisions

| Decision ID | Question | Blocking Status |
|---|---|---|
| DEC-WP-07-02-01 | Final organization-type taxonomy (DD-09) | Non-blocking — provisional data used |

## 31. Future Claude Code Execution Prompt

```text
Implement WP-07-02 — Organization Reference Types.

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
- Seed data must be explicitly labeled provisional pending DD-09.
```
