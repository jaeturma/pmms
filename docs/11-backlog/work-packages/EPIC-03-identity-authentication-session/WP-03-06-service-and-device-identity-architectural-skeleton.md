# WP-03-06 — Service and Device Identity Architectural Skeleton

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-03-06 | Title | Service and Device Identity Architectural Skeleton |
| Epic | EPIC-03 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 22 |
| Target release group | Foundation Release B | Pilot relevance | Required (pilot only) |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | BC-02 Identity/Access |
| Secondary affected contexts | EPIC-12 (Flutter device binding) | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer |

## 2. Purpose

Establishes the data-model skeleton for service identities (e.g., a future automated job/integration principal) and device identities (a mobile device bound to a user), per [../../../../01-architecture/device-and-service-identity-model.md](../../../../01-architecture/device-and-service-identity-model.md), without activating either in Phase 1 — laying groundwork for WP-12-04 (Flutter secure storage/device binding).

## 3. Architecture Sources

[../../../../01-architecture/device-and-service-identity-model.md](../../../../01-architecture/device-and-service-identity-model.md).

## 4. Scope

Design (not migrate) a `devices` table skeleton (device ID, owning user, platform, registered-at, last-seen-at) and a `service_identities` table skeleton (name, purpose, credential-reference — no credential value stored in this table); document both as skeletons, not activated.

## 5. Explicit Exclusions

Does not implement any service-identity credential issuance; does not implement device registration UI or API (WP-12-04's scope); does not create the actual migration.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-03-01 | Hard |

## 7. Current-State Inspection

No device or service-identity code exists in the repository.

## 8. Proposed Implementation Direction

Proposed: `App\Domain\Identity\Device`, `App\Domain\Identity\ServiceIdentity` skeleton entities (no Eloquent model or migration yet — schema proposal only, actualized when WP-12-04 needs it).

## 9. Database Changes

Database Changes: Proposed schema only, not created in this work package. `devices` (id, user_id FK, platform, device_label, registered_at, last_seen_at, revoked_at nullable) and `service_identities` (id, name, purpose, active, created_at) — both proposed, deferred to WP-12-04's actual migration.

## 10. Backend Requirements

Documentation/design only; no code.

## 11. Web Frontend Requirements

Not Applicable.

## 12. Flutter Requirements

This work package's schema proposal is the direct input to WP-12-04's secure-storage/device-binding implementation.

## 13. Authorization and Access Control

Documents that service identities never receive user-equivalent broad access — restated from ADR-0012's service-identity restriction principle.

## 14. Security Requirements

Documents that no credential value is ever stored in the `service_identities` table itself — only a reference to wherever the credential actually lives (a future secret-management concern, DV-03, still open).

## 15. Privacy and Data-Governance Requirements

Device records contain no personal data beyond platform/label — no location or biometric data is proposed.

## 16. Audit and Activity Events

Device registration/revocation is audit-worthy once implemented (WP-12-04, WP-06-01).

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable in this work package.

## 18. Offline and Synchronization Requirements

Device identity is the foundation for future offline-authorization snapshot validity (Phase 0.3's offline-authorization-model.md), not implemented in Phase 1.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

Not Applicable — schema proposal only, no code to test.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record proposed schema in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Design `devices` table skeleton | WP-03-01 complete | Schema proposal documented |
| TASK-02 | Design `service_identities` table skeleton | WP-03-01 complete | Schema proposal documented |
| TASK-03 | Record both proposals in `.ai/architecture.md`, explicitly marked "proposed, not created" | TASK-01..02 | Section added |

## 24. Acceptance Criteria

- **AC-01:** Given the `devices` schema proposal, when reviewed, then it contains no location or biometric field.
- **AC-02:** Given the `service_identities` schema proposal, when reviewed, then it contains no credential-value column.
- **AC-03:** Given both proposals, when documented, then neither is implemented as an actual migration in this work package.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-03-01 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both schema proposals documented and reviewed by Security reviewer.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): the two documented schema proposals.

## 28. Rollback and Recovery Considerations

Not Applicable — no migration created.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-03-06-01 | WP-12-04 implements a different schema than proposed here, causing rework | Low | This proposal is explicitly labeled non-binding, subject to WP-12-04's own review | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-03-06 — Service and Device Identity Architectural Skeleton.

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
- Do not create any migration — schema proposal only.
- Do not store any credential value in the service-identity design.
```
