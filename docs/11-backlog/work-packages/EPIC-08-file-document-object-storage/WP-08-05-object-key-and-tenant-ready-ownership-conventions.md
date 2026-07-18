# WP-08-05 — Object Key and Tenant-Ready Ownership Conventions

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-08-05 | Title | Object Key and Tenant-Ready Ownership Conventions |
| Epic | EPIC-08 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 70 |
| Target release group | Foundation Release C | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | BC-30 Document and Records |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer |

## 2. Purpose

Extends WP-04-08's tenant-isolation convention into object-storage key naming — every object key includes the organization/meet identifier explicitly, so a storage-level misconfiguration cannot cross-expose one organization's files to another, applying ADR-0012's defense-in-depth principle to a new layer (storage) for the first time.

## 3. Architecture Sources

ADR-0012, [../../../../09-enterprise/tenant-data-ownership-and-isolation-architecture.md](../../../../09-enterprise/tenant-data-ownership-and-isolation-architecture.md).

## 4. Scope

Define the object-key naming convention (proposed: `{organization_id}/{meet_id|"org"}/{ulid}.{ext}`); implement key generation in `UploadFileCommand` (WP-08-03) following this convention.

## 5. Explicit Exclusions

Does not implement per-tenant bucket separation (a single bucket with key-prefix isolation is the Phase 1 approach, per ADR-0012's shared-database-equivalent reasoning applied to storage); does not implement bucket-policy-level enforcement (a future infrastructure hardening step).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-08-02, WP-04-08 | Hard |

## 7. Current-State Inspection

WP-08-02's `storage_key` column exists without a defined naming convention yet.

## 8. Proposed Implementation Direction

Object keys are generated server-side only, from resolved context (WP-04-05), never from client input — mirroring WP-04-05's fail-closed context-resolution discipline applied to file paths.

## 9. Database Changes

Database Changes: None (uses existing `storage_key` column).

## 10. Backend Requirements

Key-generation logic embedded in `UploadFileCommand`, never accepting a client-supplied path component.

## 11. Web Frontend Requirements

Not Applicable in this work package.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Not Applicable directly — this is a storage-layer isolation convention, complementing (not replacing) WP-08-06's application-layer authorization.

## 14. Security Requirements

The convention must make path traversal impossible (no client-controllable path segment, ULID-based leaf name, no user-supplied filename embedded in the key itself — `original_filename` stays in metadata only).

## 15. Privacy and Data-Governance Requirements

Object-key structure itself must not leak information (e.g., a predictable pattern that reveals another organization's ID range) beyond what's operationally necessary.

## 16. Audit and Activity Events

Not Applicable directly.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable directly.

## 20. Testing Requirements

Unit tests: generated key always includes the resolved organization ID; a client-supplied path component in the original filename never appears in the resulting key; path-traversal-style filenames (`../../etc/passwd`) are safely handled.

## 21. Test Data Requirements

Reuses existing factories. Do not create new test data.

## 22. Documentation Updates

Record the object-key convention in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Design and implement server-side-only object-key generation | WP-08-02, WP-04-08 complete | Unit tests pass |
| TASK-02 | Test path-traversal resistance | TASK-01 | Malicious filename test passes |

## 24. Acceptance Criteria

- **AC-01:** Given any uploaded file, when its object key is generated, then it includes the resolved organization ID from server-side context, never from client input.
- **AC-02:** Given a malicious filename attempting path traversal, when uploaded, then the resulting object key is unaffected (traversal has no effect).

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-08-02, WP-04-08 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): test output for both criteria.

## 28. Rollback and Recovery Considerations

Not Applicable — new convention, no existing keys to migrate.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-08-05-01 | A future work package accepts a client-supplied path component, reintroducing traversal risk | Critical | AC-02 is a standing regression guard | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-08-05 — Object Key and Tenant-Ready Ownership Conventions.

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
- Object keys must be generated server-side only, never from client input.
- Must be resistant to path-traversal-style filenames.
```
