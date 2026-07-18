# WP-05-04 — Scope Model Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-05-04 | Title | Scope Model Foundation |
| Epic | EPIC-05 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 37 |
| Target release group | Foundation Release B | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | BC-02 Identity/Access |
| Secondary affected contexts | BC-03, BC-04 | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer |

## 2. Purpose

Implements the scope-type catalog from [scope-model.md](../../../../01-architecture/scope-model.md) — the architecture's "strongest authorization guarantee" per Phase 0.13's identity-access review, specifically its non-inheritance property (a scope grant at one level never implicitly grants access at another).

## 3. Architecture Sources

[../../../../01-architecture/scope-model.md](../../../../01-architecture/scope-model.md), [../../../10-review/identity-access-scope-and-assignment-review.md](../../../10-review/identity-access-scope-and-assignment-review.md).

## 4. Scope

Implement `scope_types` reference table (the subset of the 18 documented scope types relevant to WP-04-01/04-02's Organization/Meet contexts — e.g., organization-scope, meet-scope); document explicitly that non-Phase-1 scope types (e.g., sport-scope) are catalogued but inactive until their owning context exists.

## 5. Explicit Exclusions

Does not implement scope types for out-of-Phase-1 contexts (sport, venue-specific, etc. beyond their reference-data skeleton); does not implement the assignment binding itself (WP-05-05).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-04-01, WP-04-02 | Hard |

## 7. Current-State Inspection

`docs/01-architecture/scope-model.md`'s 18-type catalog — must be re-read to confirm which types are Phase-1-relevant.

## 8. Proposed Implementation Direction

Proposed `scope_types` table (catalog of all 18 types, each flagged `active_in_phase_1` boolean) plus a `Scope` value object (type + target ID) used by WP-05-05's assignment model — the value object itself has no database table (it's a composite reference, not an entity).

## 9. Database Changes

Proposed `scope_types` table: `id`, `code` (unique), `label`, `active_in_phase_1` (boolean). Full 18-type catalog seeded; only organization-scope and meet-scope are `active_in_phase_1 = true`.

## 10. Backend Requirements

`Scope` value object in the shared kernel or Identity domain (proposed), representing (scope_type, target_id) pairs, non-inheriting by construction (no implicit parent-child resolution logic).

## 11. Web Frontend Requirements

Not Applicable in this work package.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

This is the scope half of the authority formula (role + permission + scope + assignment) WP-05-07 evaluates.

## 14. Security Requirements

Non-inheritance must be structurally enforced (no code path implicitly grants a broader scope from a narrower one).

## 15. Privacy and Data-Governance Requirements

Not personal data.

## 16. Audit and Activity Events

One-time system-initialization event for the catalog seed.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

Unit test confirming the `Scope` value object never implicitly widens (e.g., an organization-scope grant does not automatically grant meet-scope); seeder test confirming all 18 types present with correct `active_in_phase_1` flags.

## 21. Test Data Requirements

The seeder is production reference data.

## 22. Documentation Updates

Record scope-type activation status in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement `scope_types` migration and full 18-type seed | WP-04-01, WP-04-02 complete | Seed test passes |
| TASK-02 | Implement non-inheriting `Scope` value object | TASK-01 | Non-inheritance unit test passes |

## 24. Acceptance Criteria

- **AC-01:** Given the `scope_types` catalog, when seeded, then all 18 documented types are present, with only organization-scope and meet-scope marked `active_in_phase_1`.
- **AC-02:** Given a `Scope` value object for organization-scope, when evaluated against a meet-scope requirement, then it does not implicitly satisfy it.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-04-01, WP-04-02 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): seed test output, non-inheritance unit-test output.

## 28. Rollback and Recovery Considerations

Migration reversible; seeder idempotent.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-05-04-01 | A future work package accidentally implements scope inheritance, weakening the architecture's strongest authorization guarantee | Critical | AC-02's non-inheritance test is a standing regression guard | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-05-04 — Scope Model Foundation.

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
- Scope must never implicitly inherit across levels — this is the architecture's strongest authorization guarantee and must not be weakened.
```
