# WP-05-10 — Separation-of-Duties Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-05-10 | Title | Separation-of-Duties Foundation |
| Epic | EPIC-05 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 43 |
| Target release group | Foundation Release B | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | BC-02 Identity/Access |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer |

## 2. Purpose

Implements the SOD-rule-checking mechanism (a user may not hold two specific roles simultaneously, or may not approve their own action) from [../../../10-review/identity-access-scope-and-assignment-review.md](../../../10-review/identity-access-scope-and-assignment-review.md)'s Separation of Duties table, seeding only the SOD entries **not** blocked on the OD-07/09/12/15 policy cluster (SOD-11's impersonation-never-approves rule and any other Phase-1-relevant, policy-independent rule) — the four policy-blocked entries (SOD-01/03/04/09) are catalogued but inactive, consistent with Section 5's exclusion of their owning modules.

## 3. Architecture Sources

[../../../10-review/identity-access-scope-and-assignment-review.md](../../../10-review/identity-access-scope-and-assignment-review.md).

## 4. Scope

Implement `sod_rules` table (a catalog of role/action pairs that conflict); implement an SOD-check service consulted at assignment time (WP-05-05's `AssignRoleCommand`) preventing a conflicting simultaneous assignment.

## 5. Explicit Exclusions

Does not activate SOD-01/03/04/09 (blocked on OD-07/09/12/15, out of Phase 1 scope); does not implement any UI.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-05-05 | Hard |

## 7. Current-State Inspection

`assignments` table exists per WP-05-05; no SOD-checking logic exists yet.

## 8. Proposed Implementation Direction

Proposed `sod_rules` table (`role_a_id`, `role_b_id`, `rule_type`: mutually-exclusive vs. approval-conflict, `active` boolean); an `SodRuleService` (proposed) checked within `AssignRoleCommand`'s validation.

## 9. Database Changes

Proposed `sod_rules` table: `id`, `role_a_id` (FK), `role_b_id` (FK), `rule_type`, `active` (boolean, defaults false for policy-blocked entries), `description`, timestamps.

## 10. Backend Requirements

`SodRuleService::wouldConflict(User $user, Role $newRole): bool`, consulted before persisting a new assignment.

## 11. Web Frontend Requirements

Not Applicable in this work package.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

This extends WP-05-05's assignment creation, not WP-05-07's decision evaluation — SOD is a constraint on *granting* authority, not on *checking* it.

## 14. Security Requirements

An inactive SOD rule (policy-blocked) must not silently pass as if satisfied — it is simply not enforced, and this distinction is documented explicitly to avoid a false sense of protection.

## 15. Privacy and Data-Governance Requirements

Not Applicable.

## 16. Audit and Activity Events

A blocked assignment attempt (SOD conflict) is audit-worthy once WP-06-06 exists.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable directly.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable directly.

## 20. Testing Requirements

Unit tests: an active mutually-exclusive rule blocks a conflicting assignment; an inactive (policy-blocked) rule does not block, and this non-blocking is itself asserted explicitly (not just absence of an error).

## 21. Test Data Requirements

New `SodRuleFactory` (proposed), seeded with the non-blocked subset only.

## 22. Documentation Updates

Record which SOD entries are active vs. catalogued-but-inactive in `.ai/architecture.md` addendum, explicitly cross-referencing the four policy-blocked entries.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Catalog all SOD entries from the source review, marking active/inactive per Phase 1 scope | WP-05-05 complete | Catalog documented |
| TASK-02 | Implement `sod_rules` migration, seeder, and `SodRuleService` | TASK-01 | Migration reversible, tests pass |
| TASK-03 | Wire SOD check into `AssignRoleCommand` | TASK-02 | Conflict-blocking test passes |

## 24. Acceptance Criteria

- **AC-01:** Given an active mutually-exclusive SOD rule between Role A and Role B, when a user already holding Role A is assigned Role B, then the assignment is rejected.
- **AC-02:** Given an inactive (policy-blocked) SOD rule, when the corresponding conflicting assignment is attempted, then it is allowed (not enforced), and this is explicitly documented as an accepted Phase 1 limitation, not a bug.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-05-05 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified; active/inactive distinction documented clearly.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): the SOD catalog with active/inactive status, test output.

## 28. Rollback and Recovery Considerations

Migration reversible; seeder idempotent.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-05-10-01 | Inactive SOD rules are mistaken for active protection by a future reviewer, creating false confidence | Medium | Section 22's explicit documentation and AC-02's explicit non-enforcement test | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-05-10 — Separation-of-Duties Foundation.

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
- Do not activate SOD-01/03/04/09 — they remain blocked on OD-07/09/12/15.
- Document the active/inactive distinction explicitly so it is never mistaken for full enforcement.
```
