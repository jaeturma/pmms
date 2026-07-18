# WP-13-07 — Storage Health Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-13-07 | Title | Storage Health Foundation |
| Epic | EPIC-13 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Small |
| Priority | P1 | Implementation sequence | 130 |
| Target release group | Foundation Release E | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | Cross-cutting |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Engineering lead |

## 2. Purpose

Registers an object-storage health check into the WP-13-04 framework, verifying the disk configured by WP-08-01's storage abstraction is reachable and writable — so document upload/download failures surface as an infrastructure signal rather than as scattered user-facing errors.

## 3. Architecture Sources

[../../../02-data/object-metadata-and-file-lifecycle.md](../../../02-data/object-metadata-and-file-lifecycle.md), [../../../09-enterprise/minio-object-storage-media-and-delivery-scaling.md](../../../09-enterprise/minio-object-storage-media-and-delivery-scaling.md), ADR-0005, ADR-0008.

## 4. Scope

A `HealthCheck` implementation performing a lightweight write/read/delete probe against the configured storage disk (proposed: a fixed-key probe object in a dedicated health prefix), registered via WP-13-04; reports reachable/writable status and probe latency.

## 5. Explicit Exclusions

Does not verify bucket policies, versioning, or lifecycle rules (WP-08-01/WP-08-05 own configuration); does not scan or reconcile objects (WP-08-09); does not check capacity/quota (future operational metric).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-13-04 | Hard |
| WP-08-01 | Hard |

## 7. Current-State Inspection

By this point WP-08-01 provides the storage-configuration abstraction (local/S3/MinIO-compatible); no health visibility exists for it.

## 8. Proposed Implementation Direction

`StorageHealthCheck` (proposed) using the WP-08-01 abstraction — not a direct MinIO client — so the check is valid for any configured backend; probe object under a reserved `health/` key prefix (proposed), cleaned up after each probe.

## 9. Database Changes

Database Changes: None.

## 10. Backend Requirements

One check implementation probing through the storage abstraction, with cleanup.

## 11. Web Frontend Requirements

Not Applicable.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Inherited from WP-13-04's restricted readiness endpoint.

## 14. Security Requirements

Check output must not expose endpoint URLs, bucket names, or credentials; the probe key prefix must be reserved so probe objects can never collide with real file-metadata records (WP-08-02).

## 15. Privacy and Data-Governance Requirements

Probe objects contain fixed, meaningless content — never real data.

## 16. Audit and Activity Events

Not Applicable — probe writes are infrastructure checks, not file lifecycle events; they must not generate WP-08-10 file audit events.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Makes storage failure visible as one signal; latency captured for drift detection.

## 20. Testing Requirements

Test: healthy path (probe write/read/delete succeeds); simulated unreachable storage reports down without leaking endpoint detail; probe leaves no residue object; probe does not create file-metadata or audit records.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record the check, probe-key convention, and cleanup behavior in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement storage probe check via the WP-08-01 abstraction | WP-13-04, WP-08-01 complete | Healthy and simulated-failure tests pass |
| TASK-02 | Verify probe cleanup and metadata/audit isolation | TASK-01 | No residue, no metadata, no audit events |

## 24. Acceptance Criteria

- **AC-01:** Given reachable storage, when readiness is requested, then the storage check reports healthy with probe latency and no endpoint/bucket/credential detail.
- **AC-02:** Given unreachable storage (simulated), when readiness is requested, then the check reports down without leaking configuration detail.
- **AC-03:** Given a completed probe, when storage and the database are inspected, then no probe object, file-metadata record, or audit event remains.

## 25. Definition of Ready

Per [../../phase-1-definition-of-ready.md](../../phase-1-definition-of-ready.md). WP-13-04 and WP-08-01 complete.

## 26. Definition of Done

Per [../../phase-1-definition-of-done.md](../../phase-1-definition-of-done.md). All three criteria verified.

## 27. Completion Evidence

Per [../../phase-1-completion-evidence-standard.md](../../phase-1-completion-evidence-standard.md): readiness output (healthy and failure), cleanup verification, test output.

## 28. Rollback and Recovery Considerations

Additive check; deregistration restores prior state. Probe objects are self-cleaning.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-13-07-01 | Probe writes pollute the file-metadata model or object reconciliation (WP-08-09) counts | Medium | Reserved key prefix, no metadata records, AC-03 regression; WP-08-09 excludes the health prefix by convention | Engineering lead |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-13-07 — Storage Health Foundation.

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
- Probe through the WP-08-01 storage abstraction — never a direct backend client.
- Never expose endpoint URLs, bucket names, or credentials in check output.
- Probes must be self-cleaning and must not create file-metadata or audit records.
```
